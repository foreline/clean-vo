<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

/**
 * Top-Level Domain (TLD) enumeration
 * 
 * Represents common and country-code top-level domains.
 * Can be extended to include additional TLDs as needed.
 */
enum TopLevelDomain: string
{
    // Generic TLDs
    case COM = 'com';
    case NET = 'net';
    case ORG = 'org';
    case EDU = 'edu';
    case GOV = 'gov';
    case MIL = 'mil';
    case INT = 'int';
    case INFO = 'info';
    case BIZ = 'biz';
    case NAME = 'name';
    
    // Country Code TLDs (ccTLDs)
    case RU = 'ru';
    case BY = 'by';
    case UA = 'ua';
    case KZ = 'kz';
    case US = 'us';
    case UK = 'uk';
    case DE = 'de';
    case FR = 'fr';
    case CN = 'cn';
    case JP = 'jp';
    
    // New Generic TLDs
    case IO = 'io';
    case DEV = 'dev';
    case APP = 'app';
    case TECH = 'tech';
    case CLOUD = 'cloud';
    case ONLINE = 'online';
    
    /**
     * Check if TLD is a country code
     */
    public function isCountryCode(): bool
    {
        return in_array($this, [
            self::RU, self::BY, self::UA, self::KZ,
            self::US, self::UK, self::DE, self::FR,
            self::CN, self::JP,
        ], true);
    }
    
    /**
     * Check if TLD is generic
     */
    public function isGeneric(): bool
    {
        return !$this->isCountryCode();
    }
    
    /**
     * Get human-readable name
     */
    public function getName(): string
    {
        return match($this) {
            self::COM => 'Commercial',
            self::NET => 'Network',
            self::ORG => 'Organization',
            self::EDU => 'Educational',
            self::GOV => 'Government',
            self::MIL => 'Military',
            self::INT => 'International',
            self::RU => 'Russian Federation',
            self::BY => 'Belarus',
            self::UA => 'Ukraine',
            self::KZ => 'Kazakhstan',
            self::US => 'United States',
            self::UK => 'United Kingdom',
            self::DE => 'Germany',
            self::FR => 'France',
            self::CN => 'China',
            self::JP => 'Japan',
            default => ucfirst($this->value),
        };
    }
    
    /**
     * Try to create from string (case-insensitive)
     * 
     * Note: PHP's built-in tryFrom() is case-sensitive.
     * Use this method for case-insensitive TLD lookup.
     * 
     * @param string $value TLD value (case-insensitive)
     * @return self|null Returns enum case or null if not found
     */
    public static function fromString(string $value): ?self
    {
        $normalized = mb_strtolower(trim($value));
        
        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }
        
        return null;
    }
}
