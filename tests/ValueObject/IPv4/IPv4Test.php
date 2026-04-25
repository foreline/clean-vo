<?php
declare(strict_types=1);

namespace Tests\ValueObject\IPv4;

use Domain\ValueObject\IPv4\IPv4;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class IPv4Test extends TestCase
{
    
    /**
     * @return void
     */
    public function test__construct(): void
    {
        // Arrange
        $addresses = [
            '192.168.0.0',
            '192.168.0.1',
            '172.16.1.10',
            '10.0.0.0',
            '10.10.10.10',
        ];
        
        // Act
        foreach ( $addresses as $address ) {
            $ipv4 = new IPv4($address);
    
            // Assert
            static::assertEquals($address, $ipv4->__toString(), 'Failed asserting IPv4 address');
        }
    }
}
