<?php
declare(strict_types=1);

namespace Domain\ValueObject\Money;

use Domain\ValueObject\ValueObjectInterface;

/**
 * The path to the project directory.
 */
class MoneyBag implements ValueObjectInterface
{
    /** @var Money[] */
    private array $amounts = [];
    
    /**
     * Adds a Money object to the MoneyBag.
     *
     * @param Money|MoneyBag $money The Money object to add.
     * @return $this Returns the current instance for method chaining.
     */
    public function add(Money|MoneyBag $money): self
    {
        if ( $money instanceof MoneyBag ) {
            foreach ( $money->amounts as $amount ) {
                $this->amounts[] = $amount;
            }
        } else {
            $this->amounts[] = $money;
        }
        return $this;
    }
    
    /**
     * Subtracts a Money object from the MoneyBag by adding its inverted value.
     *
     * @param Money $money
     * @return $this
     */
    public function subtract(Money $money): self
    {
        $this->amounts[] = $money->invert();
        return $this;
    }
    
    /**
     * Gets the total amount of Money in the MoneyBag for a specific currency.
     *
     * @param ?Currency|string $currency
     * @return Money
     */
    public function getAmount(Currency|string|null $currency = null): Money
    {
        if ( is_string($currency) ) {
            $currency = new Currency($currency);
        } elseif ( null === $currency ) {
            $currency = new Currency();
        }
        
        $money = new Money(0, $currency->getCode());
        
        foreach ( $this->amounts as $amount ) {
            $money = $money->plus($amount);
        }
        
        return $money;
    }
    
    /**
     * @return ValueObjectInterface[]
     */
    public static function getAll(): array
    {
        return [];
    }
}