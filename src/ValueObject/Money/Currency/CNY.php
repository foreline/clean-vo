<?php
declare(strict_types=1);

namespace Domain\ValueObject\Money\Currency;

use Domain\ValueObject\Money\CurrencyInterface;

/**
 * Китайский юань
 */
class CNY implements CurrencyInterface
{
    public const NAME = 'Китайский юань';
    public const CODE = 'CNY';
    public const NUMERIC_CODE = 156;
    public const SCALE = 2;
    public const SYMBOL = '¥';
    public const SHORTHAND = self::SYMBOL;
    
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