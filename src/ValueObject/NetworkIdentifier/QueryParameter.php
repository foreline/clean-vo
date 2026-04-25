<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use InvalidArgumentException;

/**
 * Query Parameter Value Object
 * 
 * Represents a single key-value pair in a URI query string.
 * 
 * @immutable
 */
class QueryParameter
{
    private string $key;
    private ?string $value;
    
    /**
     * Creates a new QueryParameter
     * 
     * @param string $key Parameter name
     * @param string|null $value Parameter value (null for flags like ?debug)
     * @throws InvalidArgumentException If key is empty
     */
    public function __construct(string $key, ?string $value = null)
    {
        $key = trim($key);
        
        if (empty($key)) {
            throw new InvalidArgumentException('Query parameter key cannot be empty');
        }
        
        $this->key = $key;
        $this->value = $value;
    }
    
    /**
     * Get parameter key
     */
    public function getKey(): string
    {
        return $this->key;
    }
    
    /**
     * Get parameter value
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
    
    /**
     * Check if this is a flag parameter (no value)
     */
    public function isFlag(): bool
    {
        return $this->value === null;
    }
    
    /**
     * Check if parameter has a value
     */
    public function hasValue(): bool
    {
        return $this->value !== null;
    }
    
    /**
     * Get URL-encoded representation
     */
    public function toUrlEncoded(): string
    {
        $encoded = urlencode($this->key);
        
        if ($this->value !== null) {
            $encoded .= '=' . urlencode($this->value);
        }
        
        return $encoded;
    }
    
    /**
     * Check if this parameter equals another
     */
    public function equals(QueryParameter $other): bool
    {
        return $this->key === $other->key && $this->value === $other->value;
    }
    
    /**
     * Create from URL-encoded string
     * 
     * @throws InvalidArgumentException
     */
    public static function fromUrlEncoded(string $encoded): self
    {
        $parts = explode('=', $encoded, 2);
        $key = urldecode($parts[0]);
        $value = isset($parts[1]) ? urldecode($parts[1]) : null;
        
        return new self($key, $value);
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toUrlEncoded();
    }
}
