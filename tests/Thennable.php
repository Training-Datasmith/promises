<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise\Promise;

class Thennable
{
    private $next_promise;

    public function __construct()
    {
        $this->next_promise = new Promise();
    }

    public function then(?callable $res = null, ?callable $rej = null)
    {
        return $this->next_promise->then($res, $rej);
    }

    public function resolve($value): void
    {
        $this->next_promise->resolve($value);
    }
}
