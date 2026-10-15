<?php
declare(strict_types=1);

namespace Domain\ValueObject\Web\Service;

use Domain\ValueObject\Web\WebResource;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;

/**
 * Ограниченная HTTP-загрузка с проверкой каждого адреса и перенаправления.
 * @noCodeGen
 */
class FetchPublicWebResource
{

    /** Прочитать метаданные, проверяя адрес каждого перенаправления. */
    public function fetch(string $url, float $deadline, int $maxBytes = 1048576, bool $allowTruncated = false): WebResource
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Fetching web resources requires ext-curl.');
        }
        for ($redirect = 0; $redirect <= 3; ++$redirect) {
            $parts = parse_url($url);
            if (false === $parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
                || 8192 < strlen($url) || preg_match('/[\x00-\x20\x7f]/', $url)) {
                throw new RuntimeException('Укажите полный HTTP или HTTPS URL без логина и пароля.');
            }
            $host = trim($parts['host'], '[]');
            // Symfony polyfill обеспечивает IDN без расширения intl и без Bitrix.
            if (preg_match('/[^\x00-\x7f]/', $host)) {
                $asciiHost = \idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                if (!is_string($asciiHost) || '' === $asciiHost
                    || preg_match('/[^\x00-\x7f]/', $asciiHost)) {
                    throw new RuntimeException('Не удалось преобразовать доменное имя сайта.');
                }
                $host = $asciiHost;
            }
            $ip = $this->resolvePublicAddress($host);
            $port = $parts['port'] ?? ('https' === strtolower($parts['scheme']) ? 443 : 80);
            $remaining = (int) (($deadline - microtime(true)) * 1000);
            if (0 >= $remaining) {
                throw new RuntimeException('Страница не ответила вовремя.');
            }
            $body = '';
            $location = '';
            $contentType = '';
            $requestHost = str_contains($host, ':') ? '[' . $host . ']' : $host;
            $url = (string) (new Uri($url))->withHost($requestHost)->withFragment('');
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_PROXY => '',
                CURLOPT_CONNECTTIMEOUT_MS => min(2000, $remaining),
                CURLOPT_TIMEOUT_MS => $remaining,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_ENCODING => '',
                CURLOPT_USERAGENT => 'ServiceDesk-LinkMetadata/1.0',
                CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml,image/*;q=0.9,*/*;q=0.1'],
                CURLOPT_RESOLVE => filter_var($host, FILTER_VALIDATE_IP) ? []
                    : [sprintf('%s:%d:%s', $host, $port, str_contains($ip, ':') ? '[' . $ip . ']' : $ip)],
                CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$location, &$contentType): int {
                    if (0 === stripos($header, 'Location:')) {
                        $location = trim(substr($header, 9));
                    }
                    if (0 === stripos($header, 'Content-Type:')) {
                        $contentType = trim(substr($header, 13));
                    }
                    return strlen($header);
                },
                CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, $maxBytes): int {
                    $body .= substr($chunk, 0, max(0, $maxBytes - strlen($body)));
                    return $maxBytes <= strlen($body) ? 0 : strlen($chunk);
                },
            ]);
            try {
                $success = curl_exec($curl);
                $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if (in_array($status, [301, 302, 303, 307, 308], true) && '' !== $location) {
                    $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
                    continue;
                }
                if (false === $success && !($allowTruncated && $maxBytes <= strlen($body))) {
                    throw new RuntimeException('Не удалось загрузить ресурс сайта: ошибка сети или превышен размер.');
                }
                return new WebResource($url, $body, $contentType, $status);
            } finally {
                curl_close($curl);
            }
        }
        throw new RuntimeException('Слишком много перенаправлений.');
    }


    private function resolvePublicAddress(string $host): string
    {
        $addresses = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses[] = $host;
        } else {
            foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
                $addresses[] = $record['ip'] ?? $record['ipv6'] ?? '';
            }
        }
        if ([] === $addresses) {
            throw new RuntimeException('Не удалось определить адрес сайта.');
        }
        foreach ($addresses as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                || (str_contains($ip, ':') && !preg_match('/^[23][0-9a-f]{3}:/i', $ip))
                || preg_match('/^(100\.(6[4-9]|[7-9]\d|1[01]\d|12[0-7])\.|198\.(18|19|51\.100)\.|192\.(0\.0|0\.2|88\.99)\.|203\.0\.113\.|22[4-9]\.|23\d\.|2002:|2001:0{0,4}:)/i', $ip)) {
                throw new RuntimeException('Метаданные внутренних адресов автоматически не загружаются.');
            }
        }
        return $addresses[0];
    }
}
