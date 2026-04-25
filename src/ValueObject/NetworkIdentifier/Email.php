<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\MixedValueObjectInterface;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;

/**
 * Email Value Object
 * 
 * Represents an email address with validation according to RFC 5322.
 * Supports tags (plus addressing) and provides domain access.
 * 
 * Examples:
 * - user@example.com
 * - john.doe+tag@example.com
 * - admin@subdomain.example.com
 * 
 * @immutable
 */
class Email implements MixedValueObjectInterface
{
    private const PATTERN = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';
    private const MAX_LENGTH = 254; // RFC 5321
    private const MAX_LOCAL_LENGTH = 64;
    
    private string $fullEmail;
    private string $localPart;
    private string $localPartWithoutTag;
    private ?string $tag;
    private Domain $domain;
    
    /**
     * Creates a new Email value object
     * 
     * Two construction modes:
     * 1. Single parameter: new Email('user@example.com')
     * 2. Two parameters: new Email('user', new Domain('example.com')) or new Email('user', 'example.com')
     * 
     * @param string $email Full email address OR local part if $domain is provided
     * @param string|Domain|null $domain Domain object or string (for database restoration)
     * @throws InvalidArgumentException If email format is invalid
     */
    public function __construct(string $email, string|Domain|null $domain = null)
    {
        if ($domain !== null) {
            $this->constructFromParts($email, $domain);
        } else {
            $this->constructFromFullEmail($email);
        }
    }
    
    /**
     * Construct from separate local and domain parts
     */
    private function constructFromParts(string $localPart, string|Domain $domain): void
    {
        $localPart = trim($localPart);
        
        // Convert domain string to Domain object
        if (is_string($domain)) {
            $domain = new Domain($domain);
        }
        
        // Construct full email for validation
        $fullEmail = $localPart . '@' . $domain->getFullDomain();
        
        $this->validate($fullEmail);
        
        $this->fullEmail = mb_strtolower($fullEmail);
        $this->localPart = mb_strtolower($localPart);
        $this->domain = $domain;
        $this->parseLocalPart($localPart);
    }
    
    /**
     * Construct from full email address
     */
    private function constructFromFullEmail(string $email): void
    {
        $email = mb_strtolower(trim($email));
        
        $this->validate($email);
        
        $parts = explode('@', $email);
        
        if (count($parts) !== 2) {
            throw new InvalidArgumentException(
                sprintf('Email must contain exactly one @ symbol: "%s"', $email)
            );
        }
        
        [$localPart, $domainPart] = $parts;
        
        $this->fullEmail = $email;
        $this->localPart = $localPart;
        $this->domain = new Domain($domainPart);
        $this->parseLocalPart($localPart);
    }
    
    /**
     * Validates email format
     * 
     * @throws InvalidArgumentException
     */
    private function validate(string $email): void
    {
        if (empty($email)) {
            throw new InvalidArgumentException('Email cannot be empty');
        }
        
        if (strlen($email) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Email exceeds maximum length of %d characters', self::MAX_LENGTH)
            );
        }
        
        if (!preg_match(self::PATTERN, $email)) {
            throw new InvalidArgumentException(
                sprintf('Invalid email format: "%s"', $email)
            );
        }
        
        // Validate local part length
        $localPart = explode('@', $email)[0];
        if (strlen($localPart) > self::MAX_LOCAL_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Email local part exceeds maximum length of %d characters', self::MAX_LOCAL_LENGTH)
            );
        }
    }
    
    /**
     * Parse local part to extract tag if present
     */
    private function parseLocalPart(string $localPart): void
    {
        if (str_contains($localPart, '+')) {
            $parts = explode('+', $localPart, 2);
            $this->localPartWithoutTag = $parts[0];
            $this->tag = $parts[1];
        } else {
            $this->localPartWithoutTag = $localPart;
            $this->tag = null;
        }
    }
    
    /**
     * Get full email address
     */
    public function getFullEmail(): string
    {
        return $this->fullEmail;
    }
    
    /**
     * Get local part (before @)
     */
    public function getLocalPart(): string
    {
        return $this->localPart;
    }
    
    /**
     * Get local part without tag (e.g., "user" from "user+tag@example.com")
     */
    public function getLocalPartWithoutTag(): string
    {
        return $this->localPartWithoutTag;
    }
    
    /**
     * Get tag if present (e.g., "tag" from "user+tag@example.com")
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }
    
    /**
     * Check if email has a tag
     */
    public function hasTag(): bool
    {
        return $this->tag !== null;
    }
    
    /**
     * Get domain object
     */
    public function getDomain(): Domain
    {
        return $this->domain;
    }
    
    /**
     * Check if email belongs to given domain
     */
    public function belongsToDomain(string|Domain $domain): bool
    {
        if (is_string($domain)) {
            $domain = new Domain($domain);
        }
        
        return $this->domain->equals($domain);
    }
    
    /**
     * Check if this email is equal to another
     */
    public function equals(Email $other): bool
    {
        return $this->fullEmail === $other->fullEmail;
    }
    
    /**
     * Check if this email has the same canonical address as another
     * (ignores tags when comparing)
     */
    public function isSameCanonicalAddress(Email $other): bool
    {
        return $this->localPartWithoutTag === $other->localPartWithoutTag
            && $this->domain->equals($other->domain);
    }
    
    /**
     * Create email without tag
     */
    public function withoutTag(): self
    {
        if (!$this->hasTag()) {
            return $this;
        }
        
        return new self($this->localPartWithoutTag, $this->domain);
    }
    
    /**
     * Create email with different tag
     */
    public function withTag(string $tag): self
    {
        $localPart = $this->localPartWithoutTag . '+' . $tag;
        return new self($localPart, $this->domain);
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->fullEmail;
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->fullEmail;
    }
    
    /**
     * @return array|ValueObjectInterface[]
     */
    public static function getAll(): array
    {
        return [];
    }
}
