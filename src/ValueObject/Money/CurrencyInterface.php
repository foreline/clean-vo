<?php

namespace Domain\ValueObject\Money;

/**
 *
 */
interface CurrencyInterface
{
    public function getName(): string;
    public function getCode(): string;
    public function getNumericCode(): int;
    public function getScale(): int;
    public function getSymbol(): string;
    public function getShorthand(): string;
}