<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

use Generator;
use Throwable;
/**
 * Creates a promise that is resolved using a generator that yields values or
 * promises (somewhat similar to C#'s async keyword).
 *
 * When called, the Coroutine::of method will start an instance of the generator
 * and returns a promise that is fulfilled with its final yielded value.
 *
 * Control is returned back to the generator when the yielded promise settles.
 * This can lead to less verbose code when doing lots of sequential async calls
 * with minimal processing in between.
 *
 *     use GuzzleHttp\Promise;
 *
 *     function createPromise($value) {
 *         return new Promise\FulfilledPromise($value);
 *     }
 *
 *     $promise = Promise\Coroutine::of(function () {
 *         $value = (yield createPromise('a'));
 *         try {
 *             $value = (yield createPromise($value . 'b'));
 *         } catch (\Throwable $e) {
 *             // The promise was rejected.
 *         }
 *         yield $value . 'c';
 *     });
 *
 *     // Outputs "abc"
 *     $promise->then(function ($v) { echo $v; });
 *
 * @param callable $generatorFn Generator function to wrap into a promise.
 *
 * @return Promise
 *
 * @see https://github.com/petkaantonov/bluebird/blob/master/API.md#generators inspiration
 */
final class Coroutine implements Promise_Interface
{
    /**
     * @var PromiseInterface|null
     */
    private $current_promise;
    /**
     * @var Generator
     */
    private $generator;
    /**
     * @var Promise
     */
    private $result;
    public function __construct(callable $generator_fn)
    {
        $this->generator = $generator_fn();
        $this->result = new Promise(function (): void {
            while (isset($this->current_promise)) {
                $this->current_promise->wait();
            }
        });
        try {
            $this->next_coroutine($this->generator->current());
        } catch (Throwable $throwable) {
            $this->result->reject($throwable);
        }
    }
    /**
     * Create a new coroutine.
     */
    public static function of(callable $generator_fn): self
    {
        return new self($generator_fn);
    }
    public function then(?callable $on_fulfilled = null, ?callable $on_rejected = null): Promise_Interface
    {
        return $this->result->then($on_fulfilled, $on_rejected);
    }
    public function otherwise(callable $on_rejected): Promise_Interface
    {
        return $this->result->otherwise($on_rejected);
    }
    public function wait(bool $unwrap = true)
    {
        return $this->result->wait($unwrap);
    }
    public function get_state(): string
    {
        return $this->result->get_state();
    }
    public function resolve($value): void
    {
        $this->result->resolve($value);
    }
    public function reject($reason): void
    {
        $this->result->reject($reason);
    }
    public function cancel(): void
    {
        $this->current_promise->cancel();
        $this->result->cancel();
    }
    private function next_coroutine($yielded): void
    {
        $this->current_promise = Create::promise_for($yielded)->then([$this, '_handleSuccess'], [$this, '_handleFailure']);
    }
    /**
     * @internal
     */
    public function _handle_success($value): void
    {
        unset($this->current_promise);
        try {
            $next = $this->generator->send($value);
            if ($this->generator->valid()) {
                $this->next_coroutine($next);
            } else {
                $this->result->resolve($value);
            }
        } catch (Throwable $throwable) {
            $this->result->reject($throwable);
        }
    }
    /**
     * @internal
     */
    public function _handle_failure($reason): void
    {
        unset($this->current_promise);
        try {
            $next_yield = $this->generator->throw(Create::exception_for($reason));
            // The throw was caught, so keep iterating on the coroutine
            $this->next_coroutine($next_yield);
        } catch (Throwable $throwable) {
            $this->result->reject($throwable);
        }
    }
}