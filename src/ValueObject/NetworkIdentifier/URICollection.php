<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\CollectionInterface;
use Domain\ValueObject\CollectionTrait;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;
use Iterator;

/**
 * Collection of URI Value Objects
 * 
 * Provides type-safe collection operations for URI objects.
 */
class URICollection implements CollectionInterface
{
    use CollectionTrait;
    
    /**
     * @return ?URI
     */
    public function current(): ?URI
    {
        return $this->valid() ? $this->items[$this->position] : null;
    }
    
    /**
     * @return ?URI
     */
    public function previous(): ?URI
    {
        return $this->items[$this->position - 1] ?? null;
    }
    
    /**
     * @return ?URI[]
     */
    public function getCollection(): ?array
    {
        return $this->items;
    }
    
    /**
     * Add URI to collection
     * 
     * @param ValueObjectInterface $item
     * @return self
     */
    public function addItem(ValueObjectInterface $item): self
    {
        if (!$item instanceof URI) {
            throw new InvalidArgumentException(
                sprintf('Item must be instance of URI, %s given', get_debug_type($item))
            );
        }
        
        $this->items[] = $item;
        return $this;
    }
    
    /**
     * Добавляет снимок элементов, включая безопасное добавление коллекции к самой себе.
     * 
     * @param CollectionInterface $items
     * @return self
     */
    public function addItems(CollectionInterface $items): self
    {
        foreach ($items->getCollection() ?? [] as $item) {
            $this->addItem($item);
        }
        
        return $this;
    }
    
    /**
     * Заменяет элементы после проверки типов; null очищает коллекцию.
     * 
     * @param ?Iterator $items
     * @return self
     */
    public function setItems(?Iterator $items): self
    {
        $replacement = new self();
        if (null !== $items) {
            foreach ($items as $item) {
                $replacement->addItem($item);
            }
        }

        $this->items = $replacement->items;
        $this->position = 0;

        return $this;
    }
    
    /**
     * Creates a new collection from array of URI strings
     * 
     * @param string[] $uris
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromStrings(array $uris): self
    {
        $collection = new self();
        
        foreach ($uris as $uriString) {
            $collection->addItem(new URI($uriString));
        }
        
        return $collection;
    }
    
    /**
     * Filter collection by callback
     * 
     * @param callable $callback
     * @return self
     */
    public function filter(callable $callback): self
    {
        $collection = new self();
        
        if ($this->items === null) {
            return $collection;
        }
        
        foreach ($this->items as $item) {
            if ($callback($item)) {
                $collection->addItem($item);
            }
        }
        
        return $collection;
    }
    
    /**
     * Filter by scheme
     */
    public function filterByScheme(string|Scheme $scheme): self
    {
        return $this->filter(
            fn(URI $uri) => $uri->hasScheme($scheme)
        );
    }
    
    /**
     * Filter by host
     */
    public function filterByHost(string|Domain $host): self
    {
        if ($host instanceof Domain) {
            $host = $host->getFullDomain();
        }
        
        return $this->filter(
            fn(URI $uri) => $uri->getHost() === $host
        );
    }
    
    /**
     * Get only secure URIs (HTTPS, FTPS, etc.)
     */
    public function onlySecure(): self
    {
        return $this->filter(
            fn(URI $uri) => $uri->isSecure()
        );
    }
    
    /**
     * Get only web URIs (HTTP/HTTPS)
     */
    public function onlyWeb(): self
    {
        return $this->filter(
            fn(URI $uri) => $uri->isWeb()
        );
    }
    
    /**
     * Get only URIs with query parameters
     */
    public function onlyWithQuery(): self
    {
        return $this->filter(
            fn(URI $uri) => $uri->hasQuery()
        );
    }
    
    /**
     * Get only URIs without query parameters
     */
    public function onlyWithoutQuery(): self
    {
        return $this->filter(
            fn(URI $uri) => !$uri->hasQuery()
        );
    }
    
    /**
     * Group by scheme
     * 
     * @return array<string, self>
     */
    public function groupByScheme(): array
    {
        $groups = [];
        
        if ($this->items === null) {
            return $groups;
        }
        
        foreach ($this->items as $uri) {
            $scheme = $uri->getScheme();
            
            if (!isset($groups[$scheme])) {
                $groups[$scheme] = new self();
            }
            
            $groups[$scheme]->addItem($uri);
        }
        
        return $groups;
    }
    
    /**
     * Group by host
     * 
     * @return array<string, self>
     */
    public function groupByHost(): array
    {
        $groups = [];
        
        if ($this->items === null) {
            return $groups;
        }
        
        foreach ($this->items as $uri) {
            $host = $uri->getHost() ?? '(no-host)';
            
            if (!isset($groups[$host])) {
                $groups[$host] = new self();
            }
            
            $groups[$host]->addItem($uri);
        }
        
        return $groups;
    }
    
    /**
     * Get all unique schemes in this collection
     * 
     * @return string[]
     */
    public function getUniqueSchemes(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        $schemes = array_map(
            fn(URI $uri) => $uri->getScheme(),
            $this->items
        );
        
        return array_values(array_unique($schemes));
    }
    
    /**
     * Get all unique hosts in this collection
     * 
     * @return DomainCollection
     */
    public function getUniqueHosts(): DomainCollection
    {
        $collection = new DomainCollection();
        $seen = [];
        
        if ($this->items === null) {
            return $collection;
        }
        
        foreach ($this->items as $uri) {
            $host = $uri->getHost();
            
            if ($host !== null && !isset($seen[$host])) {
                $seen[$host] = true;
                
                try {
                    $collection->addItem(new Domain($host));
                } catch (InvalidArgumentException $e) {
                    // Skip invalid domains (e.g., IP addresses)
                    continue;
                }
            }
        }
        
        return $collection;
    }
    
    /**
     * Get all base URIs (scheme + authority without path/query/fragment)
     * 
     * @return string[]
     */
    public function getBaseUris(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        return array_map(
            fn(URI $uri) => $uri->getBaseUri(),
            $this->items
        );
    }
    
    /**
     * Convert to array of URI strings
     * 
     * @return string[]
     */
    public function toStrings(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        return array_map(
            fn(URI $uri) => $uri->getFullUri(),
            $this->items
        );
    }
    
    /**
     * Apply transformation to all URIs
     */
    public function map(callable $callback): self
    {
        $collection = new self();
        
        if ($this->items === null) {
            return $collection;
        }
        
        foreach ($this->items as $uri) {
            $result = $callback($uri);
            
            if ($result instanceof URI) {
                $collection->addItem($result);
            }
        }
        
        return $collection;
    }
    
    /**
     * Change scheme for all URIs
     */
    public function withScheme(string|Scheme $scheme): self
    {
        return $this->map(
            fn(URI $uri) => $uri->withScheme($scheme)
        );
    }
    
    /**
     * Change host for all URIs
     */
    public function withHost(string|Domain $host): self
    {
        return $this->map(
            fn(URI $uri) => $uri->withHost($host)
        );
    }
    
    /**
     * Remove query parameters from all URIs
     */
    public function withoutQuery(): self
    {
        return $this->map(
            fn(URI $uri) => $uri->withoutQuery()
        );
    }
    
    /**
     * Remove fragments from all URIs
     */
    public function withoutFragment(): self
    {
        return $this->map(
            fn(URI $uri) => $uri->withoutFragment()
        );
    }
}
