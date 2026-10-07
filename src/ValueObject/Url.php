<?php
declare(strict_types=1);

namespace Domain\ValueObject;

use InvalidArgumentException;

/**
 * URL-адрес
 */
class Url implements UrlValueObjectInterface
{
    /** @var string URL-адрес */
    private string $value;

    /**
     * @param string $value URL-адрес
     * @throws InvalidArgumentException
     */
    public function __construct(string $value)
    {
        $value = trim($value);

        if ( '' !== $value && false === filter_var($value, FILTER_VALIDATE_URL) ) {
            throw new InvalidArgumentException(sprintf('Некорректный URL-адрес: "%s"', $value));
        }

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Пуст ли адрес.
     * @return bool
     */
    public function isEmpty(): bool
    {
        return '' === $this->value;
    }
}
