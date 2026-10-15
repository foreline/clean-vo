<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\IntValueObjectInterface;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;

/**
 * Port Value Object
 * 
 * Represents a network port number (1-65535).
 * 
 * @immutable
 */
class Port implements IntValueObjectInterface
{
    private const MIN_PORT = 1;
    private const MAX_PORT = 65535;
    
    private int $port;
    
    /**
     * Creates a new Port value object
     * 
     * @param int $port Port number (1-65535)
     * @throws InvalidArgumentException If port is out of valid range
     */
    public function __construct(int $port)
    {
        $this->validate($port);
        $this->port = $port;
    }
    
    /**
     * Validates port number
     * 
     * @throws InvalidArgumentException
     */
    private function validate(int $port): void
    {
        if ($port < self::MIN_PORT || $port > self::MAX_PORT) {
            throw new InvalidArgumentException(
                sprintf('Port must be between %d and %d, %d given', self::MIN_PORT, self::MAX_PORT, $port)
            );
        }
    }
    
    /**
     * Get port number
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /** Integer representation required by IntValueObjectInterface. */
    public function __toInteger(): int
    {
        return $this->port;
    }
    
    /**
     * Check if this is a well-known port (1-1023)
     */
    public function isWellKnown(): bool
    {
        return $this->port >= 1 && $this->port <= 1023;
    }
    
    /**
     * Check if this is a registered port (1024-49151)
     */
    public function isRegistered(): bool
    {
        return $this->port >= 1024 && $this->port <= 49151;
    }
    
    /**
     * Check if this is a dynamic/private port (49152-65535)
     */
    public function isDynamic(): bool
    {
        return $this->port >= 49152 && $this->port <= 65535;
    }
    
    /**
     * Check if this port is the default for given scheme
     */
    public function isDefaultFor(Scheme $scheme): bool
    {
        return $this->port === $scheme->getDefaultPort();
    }
    
    /**
     * Check if this port is equal to another
     */
    public function equals(Port $other): bool
    {
        return $this->port === $other->port;
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->port;
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->port;
    }
    
    /**
     * @return array|ValueObjectInterface[]
     */
    public static function getAll(): array
    {
        return [];
    }
}
