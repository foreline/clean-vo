<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\CollectionInterface;
use Domain\ValueObject\CollectionTrait;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;

/**
 * Collection of Email Value Objects
 * 
 * Provides type-safe collection operations for Email objects.
 */
class EmailCollection implements CollectionInterface
{
    use CollectionTrait;
    
    /** @var ?Email[] */
    private array $items = [];
    
    /**
     * @return ?Email
     */
    public function current(): ?Email
    {
        return $this->valid() ? $this->items[$this->position] : null;
    }
    
    /**
     * @return ?Email
     */
    public function previous(): ?Email
    {
        return $this->items[$this->position - 1] ?? null;
    }
    
    /**
     * @return ?Email[]
     */
    public function getCollection(): ?array
    {
        return $this->items;
    }
    
    /**
     * Add email to collection
     * 
     * @param ValueObjectInterface $item
     * @return self
     */
    public function addItem(ValueObjectInterface $item): self
    {
        if (!$item instanceof Email) {
            throw new InvalidArgumentException(
                sprintf('Item must be instance of Email, %s given', get_debug_type($item))
            );
        }
        
        $this->items[] = $item;
        return $this;
    }
    
    /**
     * Add multiple emails to collection
     * 
     * @param CollectionInterface $items
     * @return self
     */
    public function addItems(CollectionInterface $items): self
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
        
        return $this;
    }
    
    /**
     * Set items from iterator
     * 
     * @param ?\Iterator $items
     * @return self
     */
    public function setItems(?\Iterator $items): self
    {
        if ($items === null) {
            $this->items = null;
            return $this;
        }
        
        $this->items = [];
        foreach ($items as $item) {
            $this->addItem($item);
        }
        
        return $this;
    }
    
    /**
     * Creates a new collection from array of email strings
     * 
     * @param string[] $emails
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromStrings(array $emails): self
    {
        $collection = new self();
        
        foreach ($emails as $emailString) {
            $collection->addItem(new Email($emailString));
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
     * Filter by domain
     */
    public function filterByDomain(string|Domain $domain): self
    {
        return $this->filter(
            fn(Email $email) => $email->belongsToDomain($domain)
        );
    }
    
    /**
     * Filter by top-level domain
     */
    public function filterByTLD(string|TopLevelDomain $tld): self
    {
        return $this->filter(
            fn(Email $email) => $email->getDomain()->hasTLD($tld)
        );
    }
    
    /**
     * Get only emails with tags
     */
    public function onlyWithTags(): self
    {
        return $this->filter(
            fn(Email $email) => $email->hasTag()
        );
    }
    
    /**
     * Get only emails without tags
     */
    public function onlyWithoutTags(): self
    {
        return $this->filter(
            fn(Email $email) => !$email->hasTag()
        );
    }
    
    /**
     * Remove tags from all emails
     */
    public function withoutTags(): self
    {
        $collection = new self();
        
        if ($this->items === null) {
            return $collection;
        }
        
        foreach ($this->items as $email) {
            $collection->addItem($email->withoutTag());
        }
        
        return $collection;
    }
    
    /**
     * Get unique canonical addresses (without tags)
     */
    public function uniqueCanonical(): self
    {
        $canonical = [];
        $result = new self();
        
        if ($this->items === null) {
            return $result;
        }
        
        foreach ($this->items as $email) {
            $key = $email->getLocalPartWithoutTag() . '@' . $email->getDomain()->getFullDomain();
            
            if (!isset($canonical[$key])) {
                $canonical[$key] = true;
                $result->addItem($email->withoutTag());
            }
        }
        
        return $result;
    }
    
    /**
     * Group by domain
     * 
     * @return array<string, self>
     */
    public function groupByDomain(): array
    {
        $groups = [];
        
        if ($this->items === null) {
            return $groups;
        }
        
        foreach ($this->items as $email) {
            $domain = $email->getDomain()->getFullDomain();
            
            if (!isset($groups[$domain])) {
                $groups[$domain] = new self();
            }
            
            $groups[$domain]->addItem($email);
        }
        
        return $groups;
    }
    
    /**
     * Get all unique domains in this collection
     * 
     * @return DomainCollection
     */
    public function getUniqueDomains(): DomainCollection
    {
        $collection = new DomainCollection();
        $seen = [];
        
        if ($this->items === null) {
            return $collection;
        }
        
        foreach ($this->items as $email) {
            $domainStr = $email->getDomain()->getFullDomain();
            
            if (!isset($seen[$domainStr])) {
                $seen[$domainStr] = true;
                $collection->addItem($email->getDomain());
            }
        }
        
        return $collection;
    }
    
    /**
     * Convert to array of email strings
     * 
     * @return string[]
     */
    public function toStrings(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        return array_map(
            fn(Email $email) => $email->getFullEmail(),
            $this->items
        );
    }
    
    /**
     * Convert to array of local parts
     * 
     * @return string[]
     */
    public function toLocalParts(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        return array_map(
            fn(Email $email) => $email->getLocalPart(),
            $this->items
        );
    }
}

