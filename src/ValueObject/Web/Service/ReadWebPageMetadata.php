<?php
declare(strict_types=1);

namespace Domain\ValueObject\Web\Service;

use Domain\ValueObject\Web\WebPageMetadata;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;

/** Метаданные и иконка публичной страницы без привязки к сущностям и CMS. @noCodeGen */
class ReadWebPageMetadata
{
    /** Загрузить страницу один раз; отсутствие иконки не мешает чтению текста. */
    public function read(string $url): WebPageMetadata
    {
        $fetcher = new FetchPublicWebResource();
        $resource = $fetcher->fetch($url, microtime(true) + 8, 1048576, true);
        if (200 > $resource->getStatus() || 300 <= $resource->getStatus()) {
            throw new RuntimeException('Страница недоступна или требует авторизации.');
        }
        if (!preg_match('~^(text/html|application/xhtml\+xml)(?:;|$)~i', $resource->getContentType())) {
            throw new RuntimeException('По этому адресу нет HTML-страницы.');
        }
        $document = $this->parse($resource->getBody(), $resource->getContentType());
        $metadata = $this->extractDocument($document);
        $deadline = microtime(true) + 4;
        $iconError = '';
        foreach ($this->iconUrls($document, $resource->getUrl()) as $iconUrl) {
            try {
                $iconResource = $fetcher->fetch($iconUrl, $deadline, 524288);
                if (in_array($iconResource->getStatus(), [404, 410], true)) {
                    continue;
                }
                if (200 > $iconResource->getStatus() || 300 <= $iconResource->getStatus()) {
                    throw new RuntimeException('Иконка сайта недоступна.');
                }
                $icon = (new DecodeWebsiteIcon())->decode($iconResource->getBody());
                return new WebPageMetadata($metadata->getName(), $metadata->getDescription(), $icon, '', $iconResource->getUrl());
            } catch (RuntimeException | \InvalidArgumentException $e) {
                $iconError = 'Не удалось получить иконку сайта. Можно сохранить ссылку без неё.';
            }
        }
        return new WebPageMetadata($metadata->getName(), $metadata->getDescription(), null, $iconError);
    }

    /** Извлечь только текст из уже полученного HTML без HTTP-запросов. */
    public function extract(string $html, string $contentType = ''): WebPageMetadata
    {
        return $this->extractDocument($this->parse($html, $contentType));
    }

    private function extractDocument(DOMDocument $document): WebPageMetadata
    {
        $xpath = new DOMXPath($document);
        $title = $document->getElementsByTagName('title')->item(0)?->textContent ?? '';
        $description = $xpath->query('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]/@content')?->item(0)?->nodeValue ?? '';
        $clean = static fn(string $value): string => trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        return new WebPageMetadata($clean($title), $clean($description));
    }

    private function parse(string $html, string $contentType): DOMDocument
    {
        if (!class_exists(DOMDocument::class)) {
            throw new RuntimeException('Reading page metadata requires ext-dom.');
        }
        $charset = 'UTF-8';
        if (preg_match('/charset\s*=\s*["\x27]?([a-z0-9_-]+)/i', $contentType, $match)
            || preg_match('/<meta\b[^>]*charset\s*=\s*["\x27]?([a-z0-9_-]+)/i', substr($html, 0, 8192), $match)) {
            $charset = $match[1];
        }
        try {
            $html = mb_convert_encoding($html, 'UTF-8', $charset);
            $html = preg_replace('/(<meta\b[^>]*charset\s*=\s*["\x27]?)[a-z0-9_-]+/i', '${1}UTF-8', $html) ?? $html;
        } catch (\ValueError $e) {
            throw new RuntimeException('Не удалось определить кодировку страницы.', 0, $e);
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** @return list<string> Ограниченный список адресов, включая стандартный favicon.ico. */
    private function iconUrls(DOMDocument $document, string $pageUrl): array
    {
        $page = new Uri($pageUrl);
        $base = $page;
        $baseHref = $document->getElementsByTagName('base')->item(0)?->getAttribute('href');
        if ($baseHref) {
            try { $base = UriResolver::resolve($page, new Uri($baseHref)); }
            catch (\InvalidArgumentException $e) { /* Используем адрес страницы. */ }
        }
        $icons = [];
        $touchIcons = [];
        foreach ($document->getElementsByTagName('link') as $element) {
            $rel = preg_split('/\s+/', strtolower(trim($element->getAttribute('rel'))));
            $href = trim($element->getAttribute('href'));
            if ('' === $href || (!in_array('icon', $rel, true) && !in_array('apple-touch-icon', $rel, true))) {
                continue;
            }
            try {
                $uri = UriResolver::resolve($base, new Uri($href));
                if (!in_array($uri->getScheme(), ['http', 'https'], true)) { continue; }
                if (in_array('icon', $rel, true)) { $icons[] = (string) $uri; }
                else { $touchIcons[] = (string) $uri; }
            } catch (\InvalidArgumentException $e) { /* Пропускаем некорректный адрес. */ }
        }
        return array_values(array_unique([
            ...array_slice(array_merge($icons, $touchIcons), 0, 3),
            (string) $page->withPath('/favicon.ico')->withQuery('')->withFragment(''),
        ]));
    }
}
