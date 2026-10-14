<?php
declare(strict_types=1);

namespace Tests\ValueObject;

use Domain\ValueObject\CronExpression;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Domain\ValueObject\CronExpression
 */
class CronExpressionTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructValidExpression(): void
    {
        // Arrange
        $expressions = [
            '* * * * *',
            '*/30 * * * *',
            '0 */2 * * *',
            '0 9 * * 1-5',
            '15 3 1 * *',
        ];

        // Act
        foreach ( $expressions as $expression ) {
            $cron = new CronExpression($expression);

            // Assert
            static::assertSame($expression, (string) $cron, 'Failed asserting cron expression');
            static::assertSame($expression, $cron->getValue(), 'Failed asserting cron value');
            static::assertFalse($cron->isEmpty(), 'Failed asserting cron is not empty');
        }
    }

    /**
     * @return void
     */
    public function testConstructTrimsValue(): void
    {
        // Arrange
        $cron = new CronExpression('  */30 * * * *  ');

        // Act & Assert
        static::assertSame('*/30 * * * *', (string) $cron, 'Failed asserting trimmed cron expression');
    }

    /**
     * @return void
     */
    public function testConstructEmptyExpressionIsAllowed(): void
    {
        // Arrange
        $cron = new CronExpression('');

        // Act & Assert
        static::assertSame('', (string) $cron, 'Failed asserting empty cron expression');
        static::assertTrue($cron->isEmpty(), 'Failed asserting cron is empty');
    }

    /**
     * @dataProvider invalidExpressionsProvider
     * @param string $expression
     * @return void
     */
    public function testConstructInvalidExpressionThrows(string $expression): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        new CronExpression($expression);

        // Assert (expectException)
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidExpressionsProvider(): array
    {
        return [
            'plain text' => ['not a cron'],
            'too few fields' => ['* * * *'],
            'value out of range' => ['61 * * * *'],
            'too many fields' => ['* * * * * * *'],
        ];
    }
}
