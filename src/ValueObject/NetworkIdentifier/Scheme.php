<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

/**
 * URI Scheme Enum
 * 
 * Represents common URI schemes according to RFC 3986.
 */
enum Scheme: string
{
    case HTTP = 'http';
    case HTTPS = 'https';
    case FTP = 'ftp';
    case FTPS = 'ftps';
    case MAILTO = 'mailto';
    case FILE = 'file';
    case DATA = 'data';
    case WS = 'ws';
    case WSS = 'wss';
    case SSH = 'ssh';
    case TELNET = 'telnet';
    case LDAP = 'ldap';
    case LDAPS = 'ldaps';
    
    /**
     * Get default port for this scheme
     */
    public function getDefaultPort(): ?int
    {
        return match($this) {
            self::HTTP, self::WS => 80,
            self::HTTPS, self::WSS => 443,
            self::FTP => 21,
            self::FTPS => 990,
            self::SSH => 22,
            self::TELNET => 23,
            self::LDAP => 389,
            self::LDAPS => 636,
            default => null,
        };
    }
    
    /**
     * Check if this is a secure scheme
     * @return bool
     */
    public function isSecure(): bool
    {
        return match($this) {
            self::HTTPS, self::FTPS, self::WSS, self::SSH, self::LDAPS => true,
            default => false,
        };
    }
    
    /**
     * Check if this is a web scheme (HTTP/HTTPS)
     * @return bool
     */
    public function isWeb(): bool
    {
        return $this === self::HTTP || $this === self::HTTPS;
    }
    
    /**
     * Check if this is a WebSocket scheme
     */
    public function isWebSocket(): bool
    {
        return $this === self::WS || $this === self::WSS;
    }
    
    /**
     * Get scheme name in lowercase
     */
    public function getName(): string
    {
        return $this->value;
    }
    
    /**
     * Try to create from string (case-insensitive)
     * 
     * Note: PHP's built-in tryFrom() is case-sensitive.
     * Use this method for case-insensitive lookup.
     * 
     * @param string $value Scheme value (case-insensitive)
     * @return self|null Returns enum case or null if not found
     */
    public static function fromString(string $value): ?self
    {
        $value = strtolower(trim($value));
        
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        
        return null;
    }
}
