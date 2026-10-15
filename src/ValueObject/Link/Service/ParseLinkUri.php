<?php
declare(strict_types=1);

namespace Domain\ValueObject\Link\Service;

use Domain\ValueObject\Link;
use Domain\ValueObject\ShortText;
use InvalidArgumentException;

/** Extracts editable link values without fetching the resource. */
class ParseLinkUri
{
    public function parse(string $address): Link
    {
        $address = trim($address);
        $uri = (new BuildLinkUri())->build($address);
        if ('' === $uri && '' !== $address) {
            throw new InvalidArgumentException('Invalid link URI.');
        }
        $parts = parse_url($uri);
        $host = trim($parts['host'] ?? '', '[]');
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : '';
        return new Link(
            url: new ShortText($address),
            scheme: new ShortText($parts['scheme'] ?? ''),
            ip: new ShortText($ip),
            port: $parts['port'] ?? 0,
        );
    }
}
