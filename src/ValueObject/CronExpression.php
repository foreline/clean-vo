<?php
declare(strict_types=1);

namespace Domain\ValueObject;

use Cron\CronExpression as CronExpressionValidator;
use InvalidArgumentException;

/**
 * Cron-выражение расписания
 */
class CronExpression implements StringValueObjectInterface
{
    /** @var string Cron-выражение */
    private string $value;

    /**
     * Пустое значение допустимо и означает «значение не задано»
     * (например, использовать расписание по умолчанию).
     *
     * @param string $value Cron-выражение
     * @throws InvalidArgumentException
     */
    public function __construct(string $value)
    {
        $value = trim($value);

        if ( '' !== $value && !CronExpressionValidator::isValidExpression($value) ) {
            throw new InvalidArgumentException(sprintf('Некорректное cron-выражение: "%s"', $value));
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
     * Пусто ли выражение.
     * @return bool
     */
    public function isEmpty(): bool
    {
        return '' === $this->value;
    }
}
