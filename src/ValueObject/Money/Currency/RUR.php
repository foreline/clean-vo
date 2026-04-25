<?php
declare(strict_types=1);

namespace Domain\ValueObject\Money\Currency;

use Domain\ValueObject\Money\CurrencyInterface;

/**
 * Российский рубль (до деноминации)
 */
class RUR implements CurrencyInterface
{
    public const NAME = 'Российский рубль';
    public const CODE = 'RUR';
    public const NUMERIC_CODE = 810;
    public const SCALE = 0;
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