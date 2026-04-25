<?php
declare(strict_types=1);

namespace Domain\ValueObject\IPv4;

use Domain\ValueObject\StringValueObjectInterface;
use InvalidArgumentException;

/**
 *
 */
class IPv4Mask implements StringValueObjectInterface
{
    private string $cidrNotation;
    private int $prefixLength;
    private string $dottedDecimal;
    
    /**
     * @throws InvalidArgumentException if the mask is invalid
     */
    public function __construct(string $mask)
    {
        // Validate and normalize the mask
        if ( str_starts_with($mask, '/') ) {
            [$_, $prefixLength] = explode('/', $mask);
            $this->prefixLength = self::validatePrefixLength((int)$prefixLength);
            $this->cidrNotation = sprintf('/%d', $this->prefixLength);
            $this->dottedDecimal = self::prefixToDotted($this->prefixLength);
        } else {
            $this->dottedDecimal = self::normalizeDottedDecimal($mask);
            $this->prefixLength = self::calculatePrefixLength($this->dottedDecimal);
            $this->cidrNotation = sprintf('/%d', $this->prefixLength);
        }
    }
    
    /**
     * Validate prefix length (must be between 0 and 32)
     *
     * @param int $length
     * @return int
     */
    private static function validatePrefixLength(int $length): int
    {
        if ($length < 0 || $length > 32) {
            throw new InvalidArgumentException("Prefix length must be between 0 and 32");
        }
        return $length;
    }
    
    /**
     *
     * @param string $mask
     * @return string
     */
    private static function normalizeDottedDecimal(string $mask): string
    {
        $octets = array_map('intval', explode('.', $mask));
        
        if (count($octets) !== 4) {
            throw new InvalidArgumentException("Invalid mask format. Expected four octets.");
        }
        
        foreach ($octets as $octet) {
            if ($octet < 0 || $octet > 255) {
                throw new InvalidArgumentException("Each octet must be between 0 and 255");
            }
        }
        
        // Validate mask validity (each octet must be either 255 or less than previous octet)
        for ($i = 1; $i < count($octets); $i++) {
            if ($octets[$i] > $octets[$i - 1]) {
                throw new InvalidArgumentException("Invalid subnet mask format");
            }
        }
        
        return implode('.', $octets);
    }
    
    /**
     * Convert prefix length to dotted decimal notation
     *
     * @param int $prefixLength
     * @return string
     */
    private static function prefixToDotted(int $prefixLength): string
    {
        $binary = str_repeat('1', $prefixLength) . str_repeat('0', 32 - $prefixLength);
        
        $result = [];
        for ($i = 0; $i < 32; $i += 8) {
            $octet = bindec(substr($binary, $i, 8));
            $result[] = strval($octet);
        }
        
        return implode('.', $result);
    }
    
    /**
     * Calculate prefix length from dotted decimal notation
     *
     * @param string $mask
     * @return int
     */
    private static function calculatePrefixLength(string $mask): int
    {
        $binary = '';
        foreach (explode('.', $mask) as $octet) {
            $binary .= sprintf('%08b', intval($octet));
        }
        
        $prefixLength = 0;
        foreach (str_split($binary) as $bit) {
            if ($bit === '1') {
                $prefixLength++;
            } elseif ($bit === '0') {
                break;
            }
        }
        
        return $prefixLength;
    }
    
    /**
     *
     * @return string
     */
    public function getCIDRNotation(): string
    {
        return $this->cidrNotation;
    }
    
    /**
     * @return int
     */
    public function getCIDR(): int
    {
        return (int) str_replace('/', '', $this->getCIDRNotation());
    }
    
    /**
     * @return string
     */
    public function getDottedDecimal(): string
    {
        return $this->dottedDecimal;
    }
    
    /**
     * @return int
     */
    public function getPrefixLength(): int
    {
        return $this->prefixLength;
    }
    
    /**
     * @param IPv4Mask $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->prefixLength === $other->prefixLength;
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getCIDRNotation();
    }
    
    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getCIDRNotation();
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getCIDRNotation();
    }
    
    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->getCIDRNotation();
    }
    
    /**
     * @return self[]
     */
    public static function getAll(): array
    {
        $masks = [];
        for ( $mask = 32; $mask >= 0; $mask -- ) {
            $masks[] = new self('/' . $mask);
        }
        return $masks;
    }
}