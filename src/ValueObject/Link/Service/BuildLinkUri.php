<?php
declare(strict_types=1);

namespace Domain\ValueObject\Link\Service;

use InvalidArgumentException;

/** Builds resource URIs without application entities, HTML or network requests. */
class BuildLinkUri
{
    public function build(string $url = '', string $scheme = '', string $ip = '', int $port = 0): string
    {
        if (0 > $port || 65535 < $port) {
            throw new InvalidArgumentException('Link port must be between 0 and 65535.');
        }
        $address = trim($url);
        if ('' === $address) {
            $address = trim($ip);
        }
        if ('' === $address) {
            return '';
        }
        // Reject browser-obfuscated schemes and header/control characters.
        if (preg_match('/[\x00-\x20\x7f]/', $address)) {
            return '';
        }
        $scheme = strtolower(rtrim(trim($scheme), ':/'));
        if ('' !== $scheme && !preg_match('/^[a-z][a-z0-9+.-]*$/D', $scheme)) {
            return '';
        }
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $address = '[' . $address . ']';
        }
        $hasScheme = preg_match('~^[a-z][a-z0-9+.-]*:~i', $address)
            && !preg_match('~^[^/?#]+:\d+(?:[/?#]|$)~', $address);
        $uri = $hasScheme ? $address : (str_starts_with($address, '//')
            ? ('' !== $scheme ? $scheme . ':' : '') . $address
            : ('' !== $scheme ? $scheme . '://' : '//') . $address);
        $parts = parse_url($uri);
        if (false === $parts || in_array(strtolower($parts['scheme'] ?? ''), ['javascript', 'data', 'vbscript'], true)) {
            return '';
        }
        if (0 < $port && !isset($parts['port']) && isset($parts['host'])) {
            $uri = preg_replace('~^((?:[a-z][a-z0-9+.-]*:)?//[^/?#]+)~i', '$1:' . $port, $uri, 1);
        }
        return $uri;
    }
}
