<?php
declare(strict_types=1);

namespace Tests\ValueObject;

use Domain\ValueObject\Url;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Domain\ValueObject\Url
 */
class UrlTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructValidUrl(): void
    {
        // Arrange
        $addresses = [
            'https://portal.bitrix24.ru/rest/1/abc/',
            'http://example.com',
            'https://example.com:8443/path?query=1#fragment',
        ];

        // Act
        foreach ( $addresses as $address ) {
            $url = new Url($address);

            // Assert
            static::assertSame($address, (string) $url, 'Failed asserting URL address');
            static::assertSame($address, $url->getValue(), 'Failed asserting URL value');
            static::assertFalse($url->isEmpty(), 'Failed asserting URL is not empty');
        }
    }

    /**
     * @return void
     */
    public function testConstructTrimsValue(): void
    {
        // Arrange
        $address = '  https://example.com  ';

        // Act
        $url = new Url($address);

        // Assert
        static::assertSame('https://example.com', (string) $url, 'Failed asserting trimmed URL');
    }

    /**
     * @return void
     */
    public function testConstructEmptyUrlIsAllowed(): void
    {
        // Arrange
        $url = new Url('');

        // Act & Assert
        static::assertSame('', (string) $url, 'Failed asserting empty URL');
        static::assertTrue($url->isEmpty(), 'Failed asserting URL is empty');
    }

    /**
     * @return void
     */
    public function testConstructInvalidUrlThrows(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        new Url('not a url');

        // Assert (expectException)
    }
}
