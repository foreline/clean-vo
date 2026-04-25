<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use InvalidArgumentException;

/**
 * Query Parameters Collection
 * 
 * Represents a collection of query parameters in a URI.
 * Provides methods for parameter manipulation and query string generation.
 * 
 * @immutable
 */
class QueryParameters
{
    /** @var QueryParameter[] */
    private array $parameters;
    
    /**
     * Creates a new QueryParameters collection
     * 
     * @param QueryParameter[] $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = [];
        
        foreach ($parameters as $parameter) {
            if (!$parameter instanceof QueryParameter) {
                throw new InvalidArgumentException(
                    sprintf('All parameters must be QueryParameter instances, %s given', get_debug_type($parameter))
                );
            }
            
            $this->parameters[] = $parameter;
        }
    }
    
    /**
     * Parse query string into QueryParameters
     * 
     * @param string $queryString Query string without leading '?'
     * @return self
     */
    public static function fromString(string $queryString): self
    {
        $queryString = trim($queryString);
        
        if (empty($queryString)) {
            return new self([]);
        }
        
        $parameters = [];
        $pairs = explode('&', $queryString);
        
        foreach ($pairs as $pair) {
            if (empty($pair)) {
                continue;
            }
            
            $parameters[] = QueryParameter::fromUrlEncoded($pair);
        }
        
        return new self($parameters);
    }
    
    /**
     * Get all parameters
     * 
     * @return QueryParameter[]
     */
    public function getAll(): array
    {
        return $this->parameters;
    }
    
    /**
     * Get parameter by key (returns first if multiple exist)
     */
    public function get(string $key): ?QueryParameter
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->getKey() === $key) {
                return $parameter;
            }
        }
        
        return null;
    }
    
    /**
     * Get parameter value by key
     */
    public function getValue(string $key): ?string
    {
        $parameter = $this->get($key);
        return $parameter?->getValue();
    }
    
    /**
     * Get all parameters with given key
     * 
     * @return QueryParameter[]
     */
    public function getAllByKey(string $key): array
    {
        $result = [];
        
        foreach ($this->parameters as $parameter) {
            if ($parameter->getKey() === $key) {
                $result[] = $parameter;
            }
        }
        
        return $result;
    }
    
    /**
     * Check if parameter with given key exists
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Count parameters
     */
    public function count(): int
    {
        return count($this->parameters);
    }
    
    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->parameters);
    }
    
    /**
     * Add a parameter (creates new instance)
     */
    public function add(QueryParameter $parameter): self
    {
        $parameters = $this->parameters;
        $parameters[] = $parameter;
        
        return new self($parameters);
    }
    
    /**
     * Set parameter (replaces all with same key, creates new instance)
     */
    public function set(string $key, ?string $value): self
    {
        $parameters = array_filter(
            $this->parameters,
            fn(QueryParameter $p) => $p->getKey() !== $key
        );
        
        $parameters[] = new QueryParameter($key, $value);
        
        return new self(array_values($parameters));
    }
    
    /**
     * Remove parameter by key (creates new instance)
     */
    public function remove(string $key): self
    {
        $parameters = array_filter(
            $this->parameters,
            fn(QueryParameter $p) => $p->getKey() !== $key
        );
        
        return new self(array_values($parameters));
    }
    
    /**
     * Filter parameters by callback
     */
    public function filter(callable $callback): self
    {
        $parameters = array_filter($this->parameters, $callback);
        return new self(array_values($parameters));
    }
    
    /**
     * Get only flag parameters (without values)
     */
    public function onlyFlags(): self
    {
        return $this->filter(fn(QueryParameter $p) => $p->isFlag());
    }
    
    /**
     * Get only parameters with values
     */
    public function onlyWithValues(): self
    {
        return $this->filter(fn(QueryParameter $p) => $p->hasValue());
    }
    
    /**
     * Convert to query string (without leading '?')
     */
    public function toString(): string
    {
        if (empty($this->parameters)) {
            return '';
        }
        
        return implode('&', array_map(
            fn(QueryParameter $p) => $p->toUrlEncoded(),
            $this->parameters
        ));
    }
    
    /**
     * Convert to associative array
     * Note: If multiple parameters have the same key, only the last one will be in the array
     * 
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        $result = [];
        
        foreach ($this->parameters as $parameter) {
            $result[$parameter->getKey()] = $parameter->getValue();
        }
        
        return $result;
    }
    
    /**
     * Create from associative array
     * 
     * @param array<string, string|null> $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $parameters = [];
        
        foreach ($array as $key => $value) {
            $parameters[] = new QueryParameter((string)$key, $value);
        }
        
        return new self($parameters);
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
