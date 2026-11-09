<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise as P;
use Guzzle_Http\Promise\Fulfilled_Promise;
use Guzzle_Http\Promise\Promise;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guzzle_Http\Promise\Fulfilled_Promise
 */
class Fulfilled_Promise_Test extends TestCase
{
    public function testReturnsValueWhenWaitedUpon(): void
    {
        $p = new Fulfilled_Promise('foo');
        $this->assertTrue(P\Is::fulfilled($p));
        $this->assertSame('foo', $p->wait(true));
    }

    public function testCannotCancel(): void
    {
        $p = new Fulfilled_Promise('foo');
        $this->assertTrue(P\Is::fulfilled($p));
        $p->cancel();
        $this->assertSame('foo', $p->wait());
    }

    /**
     * @expectedExceptionMessage Cannot resolve a fulfilled promise
     */
    public function testCannotResolve(): void
    {
        $this->expectException(\LogicException::class);

        $p = new Fulfilled_Promise('foo');
        $p->resolve('bar');
    }

    /**
     * @expectedExceptionMessage Cannot reject a fulfilled promise
     */
    public function testCannotReject(): void
    {
        $this->expectException(\LogicException::class);

        $p = new Fulfilled_Promise('foo');
        $p->reject('bar');
    }

    public function testCanResolveWithSameValue(): void
    {
        $p = new Fulfilled_Promise('foo');
        $p->resolve('foo');
        $this->assertSame('foo', $p->wait());
    }

    public function testCannotResolveWithPromise(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Fulfilled_Promise(new Promise());
    }

    public function testReturnsSelfWhenNoOnFulfilled(): void
    {
        $p = new Fulfilled_Promise('a');
        $this->assertSame($p, $p->then());
    }

    public function testAsynchronouslyInvokesOnFulfilled(): void
    {
        $p = new Fulfilled_Promise('a');
        $r = null;
        $f = function ($d) use (&$r): void {
            $r = $d;
        };
        $p2 = $p->then($f);
        $this->assertNotSame($p, $p2);
        $this->assertNull($r);
        P\Utils::queue()->run();
        $this->assertSame('a', $r);
    }

    public function testReturnsNewRejectedWhenOnFulfilledFails(): void
    {
        $p = new Fulfilled_Promise('a');
        $f = function (): void {
            throw new \Exception('b');
        };
        $p2 = $p->then($f);
        $this->assertNotSame($p, $p2);
        try {
            $p2->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertSame('b', $e->getMessage());
        }
    }

    public function testOtherwiseIsSugarForRejections(): void
    {
        $c = null;
        $p = new Fulfilled_Promise('foo');
        $p->otherwise(function ($v) use (&$c): void {
            $c = $v;
        });
        $this->assertNull($c);
    }

    public function testDoesNotTryToFulfillTwiceDuringTrampoline(): void
    {
        $fp = new Fulfilled_Promise('a');
        $t1 = $fp->then(function ($v) {
            return $v.' b';
        });
        $t1->resolve('why!');
        $this->assertSame('why!', $t1->wait());
    }
}
