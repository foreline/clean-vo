<?php
declare(strict_types=1);

namespace Domain\ValueObject;

/**
 * Токен/пароль (секретная строка)
 */
class Token implements TokenValueObjectInterface
{
    /** @var string Значение токена */
    private string $value;

    /**
     * @param string $value Значение токена
     */
    public function __construct(string $value)
    {
        $this->value = trim($value);
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
     * @return bool
     */
    public function isEmpty(): bool
    {
        return '' === $this->value;
    }
}
