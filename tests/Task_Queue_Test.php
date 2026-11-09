<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise\Task_Queue;
use PHPUnit\Framework\TestCase;

class Task_Queue_Test extends TestCase
{
    public function testKnowsIfEmpty(): void
    {
        $tq = new Task_Queue(false);
        $this->assertTrue($tq->is_empty());
    }

    public function testKnowsIfFull(): void
    {
        $tq = new Task_Queue(false);
        $tq->add(function (): void {
        });
        $this->assertFalse($tq->is_empty());
    }

    public function testExecutesTasksInOrder(): void
    {
        $tq = new Task_Queue(false);
        $called = [];
        $tq->add(function () use (&$called): void {
            $called[] = 'a';
        });
        $tq->add(function () use (&$called): void {
            $called[] = 'b';
        });
        $tq->add(function () use (&$called): void {
            $called[] = 'c';
        });
        $tq->run();
        $this->assertSame(['a', 'b', 'c'], $called);
    }
}
