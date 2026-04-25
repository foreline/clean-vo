<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\CollectionInterface;
use Domain\ValueObject\CollectionTrait;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;

/**
 * Collection of MAC Address Value Objects
 * 
 * Provides type-safe collection operations for MacAddress objects.
 */
class MacAddressCollection implements CollectionInterface
{
    use CollectionTrait;
    
    /** @var ?MacAddress[] */
    private array $items = [];
    
    /**
     * @return ?MacAddress
     */
    public function current(): ?MacAddress
    {
        return $this->valid() ? $this->items[$this->position] : null;
    }
    
    /**
     * @return ?MacAddress
     */
    public function previous(): ?MacAddress
    {
        return $this->items[$this->position - 1] ?? null;
    }
    
    /**
     * @return ?MacAddress[]
     */
    public function getCollection(): ?array
    {
        return $this->items;
    }
    
    /**
     * Add MAC address to collection
     * 
     * @param ValueObjectInterface $item
     * @return self
     */
    public function addItem(ValueObjectInterface $item): self
    {
        if (!$item instanceof MacAddress) {
            throw new InvalidArgumentException(
                sprintf('Item must be instance of MacAddress, %s given', get_debug_type($item))
            );
        }
        
        $this->items[] = $item;
        return $this;
    }
    
    /**
     * Add multiple MAC addresses to collection
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
     * Creates a new collection from array of MAC address strings
     * 
     * @param string[] $addresses
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromStrings(array $addresses): self
    {
        $collection = new self();
        
        foreach ($addresses as $addressString) {
            $collection->addItem(new MacAddress($addressString));
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
     * Filter by OUI (vendor identifier)
     */
    public function filterByOUI(string $oui): self
    {
        return $this->filter(
            fn(MacAddress $mac) => $mac->hasOUI($oui)
        );
    }
    
    /**
     * Get only unicast addresses
     */
    public function onlyUnicast(): self
    {
        return $this->filter(
            fn(MacAddress $mac) => $mac->isUnicast()
        );
    }
    
    /**
     * Get only multicast addresses
     */
    public function onlyMulticast(): self
    {
        return $this->filter(
            fn(MacAddress $mac) => $mac->isMulticast()
        );
    }
    
    /**
     * Get only locally administered addresses
     */
    public function onlyLocallyAdministered(): self
    {
        return $this->filter(
            fn(MacAddress $mac) => $mac->isLocallyAdministered()
        );
    }
    
    /**
     * Get only universally administered addresses
     */
    public function onlyUniversallyAdministered(): self
    {
        return $this->filter(
            fn(MacAddress $mac) => $mac->isUniversallyAdministered()
        );
    }
    
    /**
     * Exclude broadcast and null addresses
     */
    public function onlyValid(): self
    {
        return $this->filter(
            fn(MacAddress $mac) => !$mac->isBroadcast() && !$mac->isNull()
        );
    }
    
    /**
     * Group by OUI (vendor)
     * 
     * @return array<string, self>
     */
    public function groupByOUI(): array
    {
        $groups = [];
        
        if ($this->items === null) {
            return $groups;
        }
        
        foreach ($this->items as $mac) {
            $oui = $mac->getOUI();
            
            if (!isset($groups[$oui])) {
                $groups[$oui] = new self();
            }
            
            $groups[$oui]->addItem($mac);
        }
        
        return $groups;
    }
    
    /**
     * Get all unique OUIs in this collection
     * 
     * @return string[]
     */
    public function getUniqueOUIs(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        $ouis = array_map(
            fn(MacAddress $mac) => $mac->getOUI(),
            $this->items
        );
        
        return array_values(array_unique($ouis));
    }
    
    /**
     * Convert to array of MAC address strings in colon notation
     * 
     * @return string[]
     */
    public function toStrings(): array
    {
        if ($this->items === null) {
            return [];
        }
        
        return array_map(
            fn(MacAddress $mac) => $mac->toColonNotation(),
            $this->items
        );
    }
    
    /**
     * Convert to array in specific format
     * 
     * @param string $format 'colon', 'hyphen', 'dot', 'plain', 'linux', 'windows'
     * @return string[]
     */
    public function toFormat(string $format): array
    {
        if ($this->items === null) {
            return [];
        }
        
        return array_map(
            fn(MacAddress $mac) => match($format) {
                'colon' => $mac->toColonNotation(),
                'hyphen' => $mac->toHyphenNotation(),
                'dot' => $mac->toDotNotation(),
                'plain' => $mac->toPlainNotation(),
                'linux' => $mac->toLinuxFormat(),
                'windows' => $mac->toWindowsFormat(),
                default => $mac->toColonNotation(),
            },
            $this->items
        );
    }
    
    /**
     * Sort by MAC address value
     */
    public function sortByAddress(): self
    {
        if ($this->items === null) {
            return $this;
        }
        
        $sorted = $this->items;
        usort($sorted, fn(MacAddress $a, MacAddress $b) => 
            strcmp($a->getAddress(), $b->getAddress())
        );
        
        $collection = new self();
        foreach ($sorted as $mac) {
            $collection->addItem($mac);
        }
        
        return $collection;
    }
    
    /**
     * Get unique MAC addresses (remove duplicates)
     */
    public function unique(): self
    {
        $seen = [];
        $collection = new self();
        
        if ($this->items === null) {
            return $collection;
        }
        
        foreach ($this->items as $mac) {
            $address = $mac->getAddress();
            
            if (!isset($seen[$address])) {
                $seen[$address] = true;
                $collection->addItem($mac);
            }
        }
        
        return $collection;
    }
    
    /**
     * Check if collection contains specific MAC address
     */
    public function contains(MacAddress $needle): bool
    {
        if ($this->items === null) {
            return false;
        }
        
        foreach ($this->items as $mac) {
            if ($mac->equals($needle)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Find MAC addresses from same vendor as given address
     */
    public function findSameVendor(MacAddress $reference): self
    {
        return $this->filter(
            fn(MacAddress $mac) => $mac->hasSameVendor($reference)
        );
    }
}
