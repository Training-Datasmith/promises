<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise as P;
use Guzzle_Http\Promise\Fulfilled_Promise;
use Guzzle_Http\Promise\Promise;
use Guzzle_Http\Promise\Promise_Interface;
use Guzzle_Http\Promise\Rejected_Promise;
use PHPUnit\Framework\TestCase;

class Create_Test extends TestCase
{
    public function testCreatesPromiseForValue(): void
    {
        $p = P\Create::promise_for('foo');
        $this->assertInstanceOf(Fulfilled_Promise::class, $p);
    }

    public function testReturnsPromiseForPromise(): void
    {
        $p = new Promise();
        $this->assertSame($p, P\Create::promise_for($p));
    }

    public function testReturnsPromiseForThennable(): void
    {
        $p = new Thennable();
        $wrapped = P\Create::promise_for($p);
        $this->assertNotSame($p, $wrapped);
        $this->assertInstanceOf(Promise_Interface::class, $wrapped);
        $p->resolve('foo');
        P\Utils::queue()->run();
        $this->assertSame('foo', $wrapped->wait());
    }

    public function testReturnsRejection(): void
    {
        $p = P\Create::rejection_for('fail');
        $this->assertInstanceOf(Rejected_Promise::class, $p);
        $this->assertSame('fail', Property_Helper::get($p, 'reason'));
    }

    public function testReturnsPromisesAsIsInRejectionFor(): void
    {
        $a = new Promise();
        $b = P\Create::rejection_for($a);
        $this->assertSame($a, $b);
    }

    public function testIterForReturnsIterator(): void
    {
        $iter = new \ArrayIterator();
        $this->assertSame($iter, P\Create::iter_for($iter));
    }
}
