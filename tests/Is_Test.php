<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise as P;
use Guzzle_Http\Promise\Fulfilled_Promise;
use Guzzle_Http\Promise\Promise;
use Guzzle_Http\Promise\Rejected_Promise;
use PHPUnit\Framework\TestCase;

class Is_Test extends TestCase
{
    public function testKnowsIfFulfilled(): void
    {
        $p = new Fulfilled_Promise(null);
        $this->assertTrue(P\Is::fulfilled($p));
        $this->assertFalse(P\Is::rejected($p));
    }

    public function testKnowsIfRejected(): void
    {
        $p = new Rejected_Promise(null);
        $this->assertTrue(P\Is::rejected($p));
        $this->assertFalse(P\Is::fulfilled($p));
    }

    public function testKnowsIfSettled(): void
    {
        $p = new Rejected_Promise(null);
        $this->assertTrue(P\Is::settled($p));
        $this->assertFalse(P\Is::pending($p));
    }

    public function testKnowsIfPending(): void
    {
        $p = new Promise();
        $this->assertFalse(P\Is::settled($p));
        $this->assertTrue(P\Is::pending($p));
    }
}
