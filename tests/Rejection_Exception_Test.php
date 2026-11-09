<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise\Rejection_Exception;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guzzle_Http\Promise\Rejection_Exception
 */
class Rejection_Exception_Test extends TestCase
{
    public function testCanGetReasonFromException(): void
    {
        $thing = new Thing1('foo');
        $e = new Rejection_Exception($thing);

        $this->assertSame($thing, $e->get_reason());
        $this->assertSame('The promise was rejected with reason: foo', $e->getMessage());
    }

    public function testCanGetReasonMessageFromJson(): void
    {
        $reason = new Thing2();
        $e = new Rejection_Exception($reason);
        $this->assertStringContainsString('{}', $e->getMessage());
    }
}
