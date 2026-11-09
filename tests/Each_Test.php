<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise as P;
use Guzzle_Http\Promise\Fulfilled_Promise;
use Guzzle_Http\Promise\Promise;
use Guzzle_Http\Promise\Rejected_Promise;
use PHPUnit\Framework\TestCase;

class Each_Test extends TestCase
{
    public function testCallsEachLimit(): void
    {
        $p = new Promise();
        $aggregate = P\Each::of_limit($p, 2);

        $p->resolve('a');
        P\Utils::queue()->run();
        $this->assertTrue(P\Is::fulfilled($aggregate));
    }

    public function testEachLimitAllRejectsOnFailure(): void
    {
        $p = [new Fulfilled_Promise('a'), new Rejected_Promise('b')];
        $aggregate = P\Each::of_limit_all($p, 2);

        P\Utils::queue()->run();
        $this->assertTrue(P\Is::rejected($aggregate));

        $result = P\Utils::inspect($aggregate);
        $this->assertSame('b', $result['reason']);
    }
}
