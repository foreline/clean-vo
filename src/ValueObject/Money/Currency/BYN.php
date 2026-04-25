<?php
declare(strict_types=1);

namespace Domain\ValueObject\Money\Currency;

use Domain\ValueObject\Money\CurrencyInterface;

/**
 * Валюта Белорусский Рубль
 */
class BYN implements CurrencyInterface
{
    public const NAME = 'Белорусский рубль';
    public const CODE = 'BYN';
    public const NUMERIC_CODE = 933;
    public const SCALE = 2;
    public const SYMBOL = 'Br';
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