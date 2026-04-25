<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\StringValueObjectInterface;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;

/**
 * Domain Value Object
 * 
 * Represents a fully qualified domain name (FQDN) or subdomain.
 * Validates domain format according to RFC 1035 and RFC 1123.
 * 
 * Examples:
 * - example.com
 * - subdomain.example.com
 * - mail.google.com
 * - localhost (special case)
 * 
 * @immutable
 */
class Domain implements ValueObjectInterface, StringValueObjectInterface
{
    private const PATTERN = '/^(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)$/';
    private const MAX_LENGTH = 253;
    private const MAX_LABEL_LENGTH = 63;
    
    private string $fullDomain;
    private string $secondLevelDomain;
    private string $topLevelDomain;
    private ?string $subdomain;
    
    /**
     * Creates a new Domain value object
     * 
     * @param string $domain Full domain name (e.g., "mail.example.com")
     * @throws InvalidArgumentException If domain format is invalid
     */
    public function __construct(string $domain)
    {
        $domain = mb_strtolower(trim($domain));
        
        $this->validate($domain);
        
        $this->fullDomain = $domain;
        $this->parseDomain($domain);
    }
    
    /**
     * Validates domain format
     * 
     * @throws InvalidArgumentException
     */
    private function validate(string $domain): void
    {
        if (empty($domain)) {
            throw new InvalidArgumentException('Domain cannot be empty');
        }
        
        if (strlen($domain) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Domain exceeds maximum length of %d characters', self::MAX_LENGTH)
            );
        }
        
        // Check for valid characters and structure
        if (!preg_match(self::PATTERN, $domain)) {
            throw new InvalidArgumentException(
                sprintf('Invalid domain format: "%s"', $domain)
            );
        }
        
        // Validate each label length
        $labels = explode('.', $domain);
        foreach ($labels as $label) {
            if (strlen($label) > self::MAX_LABEL_LENGTH) {
                throw new InvalidArgumentException(
                    sprintf('Domain label "%s" exceeds maximum length of %d characters', $label, self::MAX_LABEL_LENGTH)
                );
            }
        }
        
        // Must have at least TLD (except for localhost)
        if (count($labels) < 2 && $domain !== 'localhost') {
            throw new InvalidArgumentException(
                sprintf('Domain must have at least second-level and top-level parts: "%s"', $domain)
            );
        }
    }
    
    /**
     * Parses domain into components
     */
    private function parseDomain(string $domain): void
    {
        if ($domain === 'localhost') {
            $this->secondLevelDomain = 'localhost';
            $this->topLevelDomain = '';
            $this->subdomain = null;
            return;
        }
        
        $parts = explode('.', $domain);
        $partsCount = count($parts);
        
        // TLD is always the last part
        $this->topLevelDomain = $parts[$partsCount - 1];
        
        // Second-level domain is the second-to-last part
        $this->secondLevelDomain = $parts[$partsCount - 2];
        
        // Everything before is subdomain
        if ($partsCount > 2) {
            $subdomainParts = array_slice($parts, 0, $partsCount - 2);
            $this->subdomain = implode('.', $subdomainParts);
        } else {
            $this->subdomain = null;
        }
    }
    
    /**
     * Get full domain name
     */
    public function getFullDomain(): string
    {
        return $this->fullDomain;
    }
    
    /**
     * Get second-level domain (e.g., "example" from "mail.example.com")
     */
    public function getSecondLevelDomain(): string
    {
        return $this->secondLevelDomain;
    }
    
    /**
     * Get top-level domain (e.g., "com" from "mail.example.com")
     */
    public function getTopLevelDomain(): string
    {
        return $this->topLevelDomain;
    }
    
    /**
     * Get TLD as enum if recognized
     */
    public function getTopLevelDomainEnum(): ?TopLevelDomain
    {
        return TopLevelDomain::fromString($this->topLevelDomain);
    }
    
    /**
     * Get subdomain part (e.g., "mail" from "mail.example.com")
     * Returns null if no subdomain exists
     */
    public function getSubdomain(): ?string
    {
        return $this->subdomain;
    }
    
    /**
     * Get base domain without subdomain (e.g., "example.com" from "mail.example.com")
     */
    public function getBaseDomain(): string
    {
        if ($this->fullDomain === 'localhost') {
            return 'localhost';
        }
        
        return $this->secondLevelDomain . '.' . $this->topLevelDomain;
    }
    
    /**
     * Check if this domain has a subdomain
     */
    public function hasSubdomain(): bool
    {
        return $this->subdomain !== null;
    }
    
    /**
     * Check if domain matches given TLD
     */
    public function hasTLD(string|TopLevelDomain $tld): bool
    {
        if ($tld instanceof TopLevelDomain) {
            return $this->topLevelDomain === $tld->value;
        }
        
        return $this->topLevelDomain === mb_strtolower(trim($tld));
    }
    
    /**
     * Check if domain matches given second-level domain
     */
    public function hasSecondLevelDomain(string $sld): bool
    {
        return $this->secondLevelDomain === mb_strtolower(trim($sld));
    }
    
    /**
     * Check if this is localhost
     */
    public function isLocalhost(): bool
    {
        return $this->fullDomain === 'localhost';
    }
    
    /**
     * Check if this domain is equal to another
     */
    public function equals(Domain $other): bool
    {
        return $this->fullDomain === $other->fullDomain;
    }
    
    /**
     * Check if this domain is a subdomain of another domain
     * 
     * Example: mail.example.com is subdomain of example.com
     */
    public function isSubdomainOf(Domain $parent): bool
    {
        if ($this->equals($parent)) {
            return false;
        }
        
        return str_ends_with($this->fullDomain, '.' . $parent->fullDomain);
    }
    
    /**
     * Create domain from parts
     * 
     * @throws InvalidArgumentException
     */
    public static function fromParts(
        string $secondLevelDomain,
        string $topLevelDomain,
        ?string $subdomain = null
    ): self {
        $parts = array_filter([
            $subdomain,
            $secondLevelDomain,
            $topLevelDomain,
        ]);
        
        return new self(implode('.', $parts));
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->fullDomain;
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->fullDomain;
    }
    
    /**
     * @return array|ValueObjectInterface[]
     */
    public static function getAll(): array
    {
        return [];
    }
}