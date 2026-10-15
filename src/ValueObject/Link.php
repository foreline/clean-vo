<?php
declare(strict_types=1);

namespace Domain\ValueObject;

use Domain\ValueObject\Link\Service\BuildLinkUri;
use InvalidArgumentException;

/** Immutable resource link. Its identity is its values, never a database identifier. */
class Link implements MixedValueObjectInterface
{
    public function __construct(
        /** @var ShortText URL */
        private ShortText $url = new ShortText(''),
        /** @var ShortText Название */
        private ShortText $name = new ShortText(''),
        /** @var ShortText Протокол */
        private ShortText $scheme = new ShortText(''),
        /** @var ShortText IP */
        private ShortText $ip = new ShortText(''),
        /** @var int Порт */
        private int $port = 0,
        /** @var Text Описание */
        private Text $description = new Text(''),
        /** @var ShortText Иконка */
        private ShortText $favicon = new ShortText(''),
    ) {
        if (0 > $port || 65535 < $port) {
            throw new InvalidArgumentException('Link port must be between 0 (unspecified) and 65535.');
        }
        if ('' !== (string) $scheme && !preg_match('/^[a-z][a-z0-9+.-]*$/iD', (string) $scheme)) {
            throw new InvalidArgumentException('Invalid URI scheme.');
        }
    }

    public function getUrl(): ShortText { return $this->url; }
    public function getName(): ShortText { return $this->name; }
    public function getScheme(): ShortText { return $this->scheme; }
    public function getIp(): ShortText { return $this->ip; }
    public function getPort(): int { return $this->port; }
    public function getDescription(): Text { return $this->description; }

    /** URI/path of the icon; storage and fetching belong to the consuming application. */
    public function getFavicon(): ShortText { return $this->favicon; }

    public function getUri(): string
    {
        return (new BuildLinkUri())->build((string) $this->url, (string) $this->scheme, (string) $this->ip, $this->port);
    }

    public function equals(self $other): bool
    {
        return $this == $other;
    }

    /** Plain URI; HTML escaping belongs to presentation. */
    public function __toString(): string
    {
        return $this->getUri();
    }
}
