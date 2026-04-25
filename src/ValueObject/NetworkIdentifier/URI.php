<?php
declare(strict_types=1);

namespace Domain\ValueObject\NetworkIdentifier;

use Domain\ValueObject\MixedValueObjectInterface;
use Domain\ValueObject\ValueObjectInterface;
use InvalidArgumentException;

/**
 * URI Value Object
 * 
 * Represents a Uniform Resource Identifier according to RFC 3986.
 * Provides validation, parsing, and manipulation of URI components.
 * 
 * URI structure:
 * scheme://user:password@host:port/path?query#fragment
 * 
 * Examples:
 * - https://example.com/path/to/resource
 * - https://user@example.com:8080/path?key=value#section
 * - mailto:user@example.com
 * - file:///path/to/file
 * 
 * @immutable
 */
class URI implements MixedValueObjectInterface
{
    private string $fullUri;
    private string $scheme;
    private ?string $userInfo;
    private ?string $host;
    private ?int $port;
    private string $path;
    private ?QueryParameters $queryParameters;
    private ?string $fragment;
    
    /**
     * Creates a new URI value object
     * 
     * @param string $uri Full URI string
     * @throws InvalidArgumentException If URI format is invalid
     */
    public function __construct(string $uri)
    {
        $uri = trim($uri);
        
        $this->validate($uri);
        $this->parseUri($uri);
        
        $this->fullUri = $uri;
    }
    
    /**
     * Validates URI format
     * 
     * @throws InvalidArgumentException
     */
    private function validate(string $uri): void
    {
        if (empty($uri)) {
            throw new InvalidArgumentException('URI cannot be empty');
        }
        
        // Basic validation using PHP's parse_url
        $parsed = parse_url($uri);
        
        if ($parsed === false) {
            throw new InvalidArgumentException(
                sprintf('Invalid URI format: "%s"', $uri)
            );
        }
        
        // Scheme is required
        if (!isset($parsed['scheme'])) {
            throw new InvalidArgumentException(
                sprintf('URI must have a scheme: "%s"', $uri)
            );
        }
    }
    
    /**
     * Parse URI into components
     */
    private function parseUri(string $uri): void
    {
        $parsed = parse_url($uri);
        
        $this->scheme = $parsed['scheme'];
        $this->userInfo = $parsed['user'] ?? null;
        
        if (isset($parsed['pass'])) {
            $this->userInfo .= ':' . $parsed['pass'];
        }
        
        $this->host = $parsed['host'] ?? null;
        $this->port = $parsed['port'] ?? null;
        $this->path = $parsed['path'] ?? '';
        $this->fragment = $parsed['fragment'] ?? null;
        
        // Parse query string
        if (isset($parsed['query'])) {
            $this->queryParameters = QueryParameters::fromString($parsed['query']);
        } else {
            $this->queryParameters = new QueryParameters([]);
        }
    }
    
    /**
     * Get full URI string
     */
    public function getFullUri(): string
    {
        return $this->fullUri;
    }
    
    /**
     * Get scheme (e.g., "http", "https", "ftp")
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }
    
    /**
     * Get scheme as enum if recognized
     */
    public function getSchemeEnum(): ?Scheme
    {
        return Scheme::fromString($this->scheme);
    }
    
    /**
     * Get user info (user:password part)
     */
    public function getUserInfo(): ?string
    {
        return $this->userInfo;
    }
    
    /**
     * Get host part
     */
    public function getHost(): ?string
    {
        return $this->host;
    }
    
    /**
     * Get host as Domain object
     * 
     * @throws InvalidArgumentException If host is null or invalid domain
     */
    public function getHostAsDomain(): Domain
    {
        if ($this->host === null) {
            throw new InvalidArgumentException('Cannot create Domain from null host');
        }
        
        return new Domain($this->host);
    }
    
    /**
     * Get port number
     */
    public function getPort(): ?int
    {
        return $this->port;
    }
    
    /**
     * Get port as Port object
     * 
     * @throws InvalidArgumentException If port is null
     */
    public function getPortAsObject(): Port
    {
        if ($this->port === null) {
            throw new InvalidArgumentException('Cannot create Port from null port');
        }
        
        return new Port($this->port);
    }
    
    /**
     * Get effective port (explicit or default for scheme)
     */
    public function getEffectivePort(): ?int
    {
        if ($this->port !== null) {
            return $this->port;
        }
        
        $schemeEnum = $this->getSchemeEnum();
        return $schemeEnum?->getDefaultPort();
    }
    
    /**
     * Get path part
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * Get query parameters
     */
    public function getQueryParameters(): QueryParameters
    {
        return $this->queryParameters;
    }
    
    /**
     * Get query string (without leading '?')
     */
    public function getQueryString(): string
    {
        return $this->queryParameters->toString();
    }
    
    /**
     * Get fragment (anchor)
     */
    public function getFragment(): ?string
    {
        return $this->fragment;
    }
    
    /**
     * Check if URI has a specific component
     */
    public function hasUserInfo(): bool
    {
        return $this->userInfo !== null;
    }
    
    public function hasHost(): bool
    {
        return $this->host !== null;
    }
    
    public function hasPort(): bool
    {
        return $this->port !== null;
    }
    
    public function hasPath(): bool
    {
        return !empty($this->path);
    }
    
    public function hasQuery(): bool
    {
        return !$this->queryParameters->isEmpty();
    }
    
    public function hasFragment(): bool
    {
        return $this->fragment !== null;
    }
    
    /**
     * Check if scheme matches
     */
    public function hasScheme(string|Scheme $scheme): bool
    {
        if ($scheme instanceof Scheme) {
            return $this->scheme === $scheme->value;
        }
        
        return $this->scheme === strtolower(trim($scheme));
    }
    
    /**
     * Check if this is a secure URI (HTTPS, FTPS, etc.)
     */
    public function isSecure(): bool
    {
        $schemeEnum = $this->getSchemeEnum();
        return $schemeEnum?->isSecure() ?? false;
    }
    
    /**
     * Check if this is a web URI (HTTP or HTTPS)
     */
    public function isWeb(): bool
    {
        $schemeEnum = $this->getSchemeEnum();
        return $schemeEnum?->isWeb() ?? false;
    }
    
    /**
     * Check if this URI equals another
     */
    public function equals(URI $other): bool
    {
        return $this->fullUri === $other->fullUri;
    }
    
    /**
     * Get authority part (user:password@host:port)
     */
    public function getAuthority(): ?string
    {
        if ($this->host === null) {
            return null;
        }
        
        $authority = '';
        
        if ($this->userInfo !== null) {
            $authority .= $this->userInfo . '@';
        }
        
        $authority .= $this->host;
        
        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }
        
        return $authority;
    }
    
    /**
     * Get base URI (scheme + authority)
     */
    public function getBaseUri(): string
    {
        $base = $this->scheme . ':';
        
        if ($this->host !== null) {
            $base .= '//' . $this->getAuthority();
        }
        
        return $base;
    }
    
    /**
     * Create URI with different scheme
     */
    public function withScheme(string|Scheme $scheme): self
    {
        if ($scheme instanceof Scheme) {
            $scheme = $scheme->value;
        }
        
        $uri = $scheme . ':';
        
        if ($this->host !== null) {
            $uri .= '//' . $this->getAuthority();
        }
        
        $uri .= $this->path;
        
        if ($this->hasQuery()) {
            $uri .= '?' . $this->getQueryString();
        }
        
        if ($this->fragment !== null) {
            $uri .= '#' . $this->fragment;
        }
        
        return new self($uri);
    }
    
    /**
     * Create URI with different host
     */
    public function withHost(string|Domain $host): self
    {
        if ($host instanceof Domain) {
            $host = $host->getFullDomain();
        }
        
        $authority = '';
        
        if ($this->userInfo !== null) {
            $authority .= $this->userInfo . '@';
        }
        
        $authority .= $host;
        
        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }
        
        $uri = $this->scheme . '://' . $authority . $this->path;
        
        if ($this->hasQuery()) {
            $uri .= '?' . $this->getQueryString();
        }
        
        if ($this->fragment !== null) {
            $uri .= '#' . $this->fragment;
        }
        
        return new self($uri);
    }
    
    /**
     * Create URI with different port
     */
    public function withPort(?int $port): self
    {
        $authority = '';
        
        if ($this->userInfo !== null) {
            $authority .= $this->userInfo . '@';
        }
        
        $authority .= $this->host;
        
        if ($port !== null) {
            $authority .= ':' . $port;
        }
        
        $uri = $this->scheme . '://' . $authority . $this->path;
        
        if ($this->hasQuery()) {
            $uri .= '?' . $this->getQueryString();
        }
        
        if ($this->fragment !== null) {
            $uri .= '#' . $this->fragment;
        }
        
        return new self($uri);
    }
    
    /**
     * Create URI with different path
     */
    public function withPath(string $path): self
    {
        $uri = $this->getBaseUri() . $path;
        
        if ($this->hasQuery()) {
            $uri .= '?' . $this->getQueryString();
        }
        
        if ($this->fragment !== null) {
            $uri .= '#' . $this->fragment;
        }
        
        return new self($uri);
    }
    
    /**
     * Create URI with different query parameters
     */
    public function withQueryParameters(QueryParameters $queryParameters): self
    {
        $uri = $this->getBaseUri() . $this->path;
        
        if (!$queryParameters->isEmpty()) {
            $uri .= '?' . $queryParameters->toString();
        }
        
        if ($this->fragment !== null) {
            $uri .= '#' . $this->fragment;
        }
        
        return new self($uri);
    }
    
    /**
     * Create URI with added query parameter
     */
    public function withAddedQueryParameter(string $key, ?string $value): self
    {
        $queryParameters = $this->queryParameters->add(new QueryParameter($key, $value));
        return $this->withQueryParameters($queryParameters);
    }
    
    /**
     * Create URI with updated query parameter (replaces existing)
     */
    public function withQueryParameter(string $key, ?string $value): self
    {
        $queryParameters = $this->queryParameters->set($key, $value);
        return $this->withQueryParameters($queryParameters);
    }
    
    /**
     * Create URI without specific query parameter
     */
    public function withoutQueryParameter(string $key): self
    {
        $queryParameters = $this->queryParameters->remove($key);
        return $this->withQueryParameters($queryParameters);
    }
    
    /**
     * Create URI without query string
     */
    public function withoutQuery(): self
    {
        return $this->withQueryParameters(new QueryParameters([]));
    }
    
    /**
     * Create URI with different fragment
     */
    public function withFragment(?string $fragment): self
    {
        $uri = $this->getBaseUri() . $this->path;
        
        if ($this->hasQuery()) {
            $uri .= '?' . $this->getQueryString();
        }
        
        if ($fragment !== null) {
            $uri .= '#' . $fragment;
        }
        
        return new self($uri);
    }
    
    /**
     * Create URI without fragment
     */
    public function withoutFragment(): self
    {
        return $this->withFragment(null);
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->fullUri;
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->fullUri;
    }
    
    /**
     * @return array|ValueObjectInterface[]
     */
    public static function getAll(): array
    {
        return [];
    }
}
