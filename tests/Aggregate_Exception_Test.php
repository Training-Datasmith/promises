<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise\Aggregate_Exception;
use PHPUnit\Framework\TestCase;

class Aggregate_Exception_Test extends TestCase
{
    public function testHasReason(): void
    {
        $e = new Aggregate_Exception('foo', ['baz', 'bar']);
        $this->assertStringContainsString('foo', $e->getMessage());
        $this->assertSame(['baz', 'bar'], $e->get_reason());
    }
}
