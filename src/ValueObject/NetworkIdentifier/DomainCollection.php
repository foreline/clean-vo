<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\CollectionInterface;
use Domain\ValueObject\CollectionTrait;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;
use Iterator;

/**
 * Collection of Domain Value Objects
 * 
 * Provides type-safe collection operations for Domain objects.
 */
class DomainCollection implements CollectionInterface
{
    use CollectionTrait;
    
    /**
     * @return ?Domain
     */
    public function current(): ?Domain
    {
        return $this->valid() ? $this->items[$this->position] : null;
    }
    
    /**
     * @return ?Domain
     */
    public function previous(): ?Domain
    {
        return $this->items[$this->position - 1] ?? null;
    }
    
    /**
     * @return ?Domain[]
     */
    public function getCollection(): ?array
    {
        return $this->items;
    }
    
    /**
     * Add domain to collection
     * 
     * @param ValueObjectInterface $item
     * @return self
     */
    public function addItem(ValueObjectInterface $item): self
    {
        if (!$item instanceof Domain) {
            throw new InvalidArgumentException(
                sprintf('Item must be instance of Domain, %s given', get_debug_type($item))
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
     * Creates a new collection from array of domain strings
     * 
     * @param string[] $domains
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromStrings(array $domains): self
    {
        $collection = new self();
        
        foreach ($domains as $domainString) {
            $collection->addItem(new Domain($domainString));
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
     * Filter by top-level domain
     */
    public function filterByTLD(string|TopLevelDomain $tld): self
    {
        return $this->filter(
            fn(Domain $domain) => $domain->hasTLD($tld)
        );
    }
    
    /**
     * Filter by second-level domain
     */
    public function filterBySLD(string $sld): self
    {
        return $this->filter(
            fn(Domain $domain) => $domain->hasSecondLevelDomain($sld)
        );
    }
    
    /**
     * Get only domains with subdomains
     */
    public function onlyWithSubdomains(): self
    {
        return $this->filter(
            fn(Domain $domain) => $domain->hasSubdomain()
        );
    }
    
    /**
     * Get only base domains (without subdomains)
     */
    public function onlyBaseDomains(): self
    {
        return $this->filter(
            fn(Domain $domain) => !$domain->hasSubdomain()
        );
    }
    
    /**
     * Group by top-level domain
     * 
     * @return array<string, self>
     */
    public function groupByTLD(): array
    {
        $groups = [];
        
        if ($this->items === null) {
            return $groups;
        }
        
        foreach ($this->items as $domain) {
            $tld = $domain->getTopLevelDomain();
            
            if (!isset($groups[$tld])) {
                $groups[$tld] = new self();
            }
            
            $groups[$tld]->addItem($domain);
        }
        
        return $groups;
    }
    
    /**
     * Get all unique TLDs in this collection
     * 
     * @return string[]
     */
    public function getUniqueTLDs(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        $tlds = array_map(
            fn(Domain $domain) => $domain->getTopLevelDomain(),
            $this->items
        );
        
        return array_values(array_unique($tlds));
    }
    
    /**
     * Get all base domains (without subdomains)
     * 
     * @return string[]
     */
    public function getBaseDomains(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        return array_map(
            fn(Domain $domain) => $domain->getBaseDomain(),
            $this->items
        );
    }
    
    /**
     * Convert to array of domain strings
     * 
     * @return string[]
     */
    public function toStrings(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        return array_map(
            fn(Domain $domain) => $domain->getFullDomain(),
            $this->items
        );
    }
}

