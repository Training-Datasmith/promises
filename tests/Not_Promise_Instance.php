<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

use Guzzle_Http\Promise\Promise;
use Guzzle_Http\Promise\Promise_Interface;

class Not_Promise_Instance extends Thennable implements Promise_Interface
{
    private $next_promise;

    public function __construct()
    {
        $this->next_promise = new Promise();
    }

    public function then(?callable $res = null, ?callable $rej = null): Promise_Interface
    {
        return $this->next_promise->then($res, $rej);
    }

    public function otherwise(callable $on_rejected): Promise_Interface
    {
        return $this->then($on_rejected);
    }

    public function resolve($value): void
    {
        $this->next_promise->resolve($value);
    }

    public function reject($reason): void
    {
        $this->next_promise->reject($reason);
    }

    public function wait(bool $unwrap = true, ?bool $defaultResolution = null): void
    {
    }

    public function cancel(): void
    {
    }

    public function get_state(): string
    {
        return $this->next_promise->get_state();
    }
}
