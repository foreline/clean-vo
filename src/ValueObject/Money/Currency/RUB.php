<?php
declare(strict_types=1);

namespace Domain\ValueObject\Money\Currency;

use Domain\ValueObject\Money\CurrencyInterface;

/**
 * Российский рубль
 */
class RUB implements CurrencyInterface
{
    public const NAME = 'Российский рубль';
    public const CODE = 'RUB';
    public const NUMERIC_CODE = 643;
    public const SCALE = 2;
    public const SYMBOL = '₽';
    public const SHORTHAND = 'руб.';
    
    public function getName(): string
    {
        return self::NAME;
    }
    
    public function getCode(): string
    {
        return self::CODE;
    }
    
    public function getNumericCode(): int
    {
        return self::NUMERIC_CODE;
    }
    
    public function getScale(): int
    {
        return self::SCALE;
    }
    
    public function getSymbol(): string
    {
        return self::SYMBOL;
    }
    
    public function getShorthand(): string
    {
        return self::SHORTHAND;
    }
}