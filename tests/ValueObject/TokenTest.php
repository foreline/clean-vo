<?php
declare(strict_types=1);

namespace Tests\ValueObject;

use Domain\ValueObject\Token;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Domain\ValueObject\Token
 */
class TokenTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructKeepsValue(): void
    {
        // Arrange
        $value = 'abc123secret';

        // Act
        $token = new Token($value);

        // Assert
        static::assertSame($value, (string) $token, 'Failed asserting token value');
        static::assertSame($value, $token->getValue(), 'Failed asserting token getter');
        static::assertFalse($token->isEmpty(), 'Failed asserting token is not empty');
    }

    /**
     * @return void
     */
    public function testConstructTrimsValue(): void
    {
        // Arrange
        $token = new Token("  abc123\t");

        // Act & Assert
        static::assertSame('abc123', (string) $token, 'Failed asserting trimmed token');
    }

    /**
     * @return void
     */
    public function testEmptyToken(): void
    {
        // Arrange
        $token = new Token('');

        // Act & Assert
        static::assertSame('', (string) $token, 'Failed asserting empty token');
        static::assertTrue($token->isEmpty(), 'Failed asserting token is empty');
    }
}
