<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\StringValueObjectInterface;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;

/**
 * MAC Address Value Object
 * 
 * Represents a Media Access Control (MAC) address according to IEEE 802 standard.
 * Supports various formats and provides vendor OUI information.
 * 
 * Supported formats:
 * - Colon notation: 00:1A:2B:3C:4D:5E
 * - Hyphen notation: 00-1A-2B-3C-4D-5E
 * - Dot notation: 001A.2B3C.4D5E (Cisco style)
 * - No separator: 001A2B3C4D5E
 * 
 * @immutable
 */
class MacAddress implements ValueObjectInterface, StringValueObjectInterface
{
    private const PATTERN_COLON = '/^([0-9A-Fa-f]{2}[:]){5}([0-9A-Fa-f]{2})$/';
    private const PATTERN_HYPHEN = '/^([0-9A-Fa-f]{2}[-]){5}([0-9A-Fa-f]{2})$/';
    private const PATTERN_DOT = '/^([0-9A-Fa-f]{4}[.]){2}([0-9A-Fa-f]{4})$/';
    private const PATTERN_PLAIN = '/^[0-9A-Fa-f]{12}$/';
    
    private string $address;
    private string $oui;
    private string $nic;
    
    /**
     * Creates a new MAC address value object
     * 
     * @param string $address MAC address in any supported format
     * @throws InvalidArgumentException If MAC address format is invalid
     */
    public function __construct(string $address)
    {
        $address = trim($address);
        
        $this->validate($address);
        
        // Normalize to uppercase without separators
        $normalized = $this->normalize($address);
        
        $this->address = $normalized;
        $this->oui = substr($normalized, 0, 6);
        $this->nic = substr($normalized, 6, 6);
    }
    
    /**
     * Validates MAC address format
     * 
     * @throws InvalidArgumentException
     */
    private function validate(string $address): void
    {
        if (empty($address)) {
            throw new InvalidArgumentException('MAC address cannot be empty');
        }
        
        $isValid = preg_match(self::PATTERN_COLON, $address)
            || preg_match(self::PATTERN_HYPHEN, $address)
            || preg_match(self::PATTERN_DOT, $address)
            || preg_match(self::PATTERN_PLAIN, $address);
        
        if (!$isValid) {
            throw new InvalidArgumentException(
                sprintf('Invalid MAC address format: "%s"', $address)
            );
        }
    }
    
    /**
     * Normalize MAC address to uppercase without separators
     */
    private function normalize(string $address): string
    {
        // Remove all separators
        $normalized = str_replace([':', '-', '.'], '', $address);
        
        // Convert to uppercase
        return strtoupper($normalized);
    }
    
    /**
     * Get MAC address in normalized form (no separators, uppercase)
     */
    public function getAddress(): string
    {
        return $this->address;
    }
    
    /**
     * Get Organizationally Unique Identifier (OUI) - first 3 octets
     * Identifies the manufacturer/vendor
     */
    public function getOUI(): string
    {
        return $this->oui;
    }
    
    /**
     * Get Network Interface Controller (NIC) specific part - last 3 octets
     */
    public function getNIC(): string
    {
        return $this->nic;
    }
    
    /**
     * Get MAC address in colon notation (00:1A:2B:3C:4D:5E)
     */
    public function toColonNotation(): string
    {
        return implode(':', str_split($this->address, 2));
    }
    
    /**
     * Get MAC address in hyphen notation (00-1A-2B-3C-4D-5E)
     */
    public function toHyphenNotation(): string
    {
        return implode('-', str_split($this->address, 2));
    }
    
    /**
     * Get MAC address in dot notation (001A.2B3C.4D5E) - Cisco style
     */
    public function toDotNotation(): string
    {
        return implode('.', str_split($this->address, 4));
    }
    
    /**
     * Get MAC address without separators (001A2B3C4D5E)
     */
    public function toPlainNotation(): string
    {
        return $this->address;
    }
    
    /**
     * Get MAC address in lowercase colon notation (00:1a:2b:3c:4d:5e)
     * Common in Linux systems
     */
    public function toLinuxFormat(): string
    {
        return strtolower($this->toColonNotation());
    }
    
    /**
     * Get MAC address in uppercase hyphen notation (00-1A-2B-3C-4D-5E)
     * Common in Windows systems
     */
    public function toWindowsFormat(): string
    {
        return $this->toHyphenNotation();
    }
    
    /**
     * Check if this is a unicast address (bit 0 of first octet is 0)
     */
    public function isUnicast(): bool
    {
        $firstOctet = hexdec(substr($this->address, 0, 2));
        return ($firstOctet & 0x01) === 0;
    }
    
    /**
     * Check if this is a multicast address (bit 0 of first octet is 1)
     */
    public function isMulticast(): bool
    {
        return !$this->isUnicast();
    }
    
    /**
     * Check if this is a locally administered address (bit 1 of first octet is 1)
     */
    public function isLocallyAdministered(): bool
    {
        $firstOctet = hexdec(substr($this->address, 0, 2));
        return ($firstOctet & 0x02) !== 0;
    }
    
    /**
     * Check if this is a universally administered address (bit 1 of first octet is 0)
     */
    public function isUniversallyAdministered(): bool
    {
        return !$this->isLocallyAdministered();
    }
    
    /**
     * Check if this is a broadcast address (FF:FF:FF:FF:FF:FF)
     */
    public function isBroadcast(): bool
    {
        return $this->address === 'FFFFFFFFFFFF';
    }
    
    /**
     * Check if this is a null address (00:00:00:00:00:00)
     */
    public function isNull(): bool
    {
        return $this->address === '000000000000';
    }
    
    /**
     * Check if OUI matches given value
     */
    public function hasOUI(string $oui): bool
    {
        $normalized = str_replace([':', '-', '.'], '', strtoupper(trim($oui)));
        return $this->oui === $normalized;
    }
    
    /**
     * Check if this MAC address equals another
     */
    public function equals(MacAddress $other): bool
    {
        return $this->address === $other->address;
    }
    
    /**
     * Check if this MAC address belongs to the same vendor (same OUI)
     */
    public function hasSameVendor(MacAddress $other): bool
    {
        return $this->oui === $other->oui;
    }
    
    /**
     * Create MAC address from octets array
     * 
     * @param array<int> $octets Array of 6 integers (0-255)
     * @throws InvalidArgumentException
     */
    public static function fromOctets(array $octets): self
    {
        if (count($octets) !== 6) {
            throw new InvalidArgumentException('MAC address must have exactly 6 octets');
        }
        
        foreach ($octets as $octet) {
            if (!is_int($octet) || $octet < 0 || $octet > 255) {
                throw new InvalidArgumentException(
                    sprintf('Each octet must be an integer between 0 and 255, got: %s', var_export($octet, true))
                );
            }
        }
        
        $hex = array_map(fn($octet) => sprintf('%02X', $octet), $octets);
        return new self(implode('', $hex));
    }
    
    /**
     * Get MAC address as array of octets
     * 
     * @return array<int> Array of 6 integers (0-255)
     */
    public function toOctets(): array
    {
        $pairs = str_split($this->address, 2);
        return array_map('hexdec', $pairs);
    }
    
    /**
     * Create a random MAC address
     * 
     * @param bool $locallyAdministered If true, creates a locally administered address
     */
    public static function random(bool $locallyAdministered = true): self
    {
        $octets = [];
        
        // Generate first octet
        $firstOctet = random_int(0, 255);
        
        // Set unicast bit (bit 0 = 0)
        $firstOctet = $firstOctet & 0xFE;
        
        // Set local/universal bit
        if ($locallyAdministered) {
            $firstOctet = $firstOctet | 0x02; // Set bit 1 = 1 (local)
        } else {
            $firstOctet = $firstOctet & 0xFD; // Set bit 1 = 0 (universal)
        }
        
        $octets[] = $firstOctet;
        
        // Generate remaining 5 octets
        for ($i = 1; $i < 6; $i++) {
            $octets[] = random_int(0, 255);
        }
        
        return self::fromOctets($octets);
    }
    
    /**
     * Get binary representation
     */
    public function toBinary(): string
    {
        $octets = $this->toOctets();
        return implode('.', array_map(
            fn($octet) => sprintf('%08b', $octet),
            $octets
        ));
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toColonNotation();
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->toColonNotation();
    }
    
    /**
     * Convert to binary format for MySQL BINARY(6) storage
     * 
     * Returns 6-byte binary string suitable for BINARY(6) column.
     * Most efficient storage format.
     * 
     * @return string Binary string (6 bytes)
     */
    public function toBinaryString(): string
    {
        return pack('C*', ...$this->toOctets());
    }
    
    /**
     * Create MAC address from binary format (MySQL BINARY(6))
     * 
     * @param string $binary Binary string (6 bytes)
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromBinaryString(string $binary): self
    {
        if (strlen($binary) !== 6) {
            throw new InvalidArgumentException(
                sprintf('Binary MAC address must be exactly 6 bytes, got %d bytes', strlen($binary))
            );
        }
        
        $octets = unpack('C*', $binary);
        return self::fromOctets(array_values($octets));
    }
    
    /**
     * Get database representation array for hybrid storage
     * 
     * Returns array with keys for recommended database columns:
     * - mac_address (BINARY(6)): Binary format for efficient storage
     * - mac_address_text (VARCHAR(17)): Text format for display/searching
     * - mac_oui (CHAR(6)): Vendor prefix for fast filtering
     * 
     * @return array{mac_address: string, mac_address_text: string, mac_oui: string}
     */
    public function toDatabaseArray(): array
    {
        return [
            'mac_address' => $this->toBinaryString(),
            'mac_address_text' => $this->toColonNotation(),
            'mac_oui' => $this->oui,
        ];
    }
    
    /**
     * @return array|ValueObjectInterface[]
     */
    public static function getAll(): array
    {
        return [];
    }
}
