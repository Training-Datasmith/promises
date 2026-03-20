<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

/**
 * Promises/A+ implementation that avoids recursion when possible.
 *
 * @see https://promisesaplus.com/
 *
 * @final
 */
class Promise implements Promise_Interface
{
    private $state = self::PENDING;
    private $result;
    private $cancel_fn;
    private $wait_fn;
    private $wait_list;
    private $handlers = [];
    /**
     * @param callable $waitFn   Fn that when invoked resolves the promise.
     * @param callable $cancelFn Fn that when invoked cancels the promise.
     */
    public function __construct(?callable $wait_fn = null, ?callable $cancel_fn = null)
    {
        $this->wait_fn = $wait_fn;
        $this->cancel_fn = $cancel_fn;
    }
    public function then(?callable $on_fulfilled = null, ?callable $on_rejected = null): Promise_Interface
    {
        if ($this->state === self::PENDING) {
            $p = new Promise(null, [$this, 'cancel']);
            $this->handlers[] = [$p, $on_fulfilled, $on_rejected];
            $p->wait_list = $this->wait_list;
            $p->wait_list[] = $this;
            return $p;
        }
        // Return a fulfilled promise and immediately invoke any callbacks.
        if ($this->state === self::FULFILLED) {
            $promise = Create::promise_for($this->result);
            return $on_fulfilled ? $promise->then($on_fulfilled) : $promise;
        }
        // It's either cancelled or rejected, so return a rejected promise
        // and immediately invoke any callbacks.
        $rejection = Create::rejection_for($this->result);
        return $on_rejected ? $rejection->then(null, $on_rejected) : $rejection;
    }
    public function otherwise(callable $on_rejected): Promise_Interface
    {
        return $this->then(null, $on_rejected);
    }
    public function wait(bool $unwrap = true)
    {
        $this->wait_if_pending();
        if ($this->result instanceof Promise_Interface) {
            return $this->result->wait($unwrap);
        }
        if ($unwrap) {
            if ($this->state === self::FULFILLED) {
                return $this->result;
            }
            // It's rejected so "unwrap" and throw an exception.
            throw Create::exception_for($this->result);
        }
    }
    public function get_state(): string
    {
        return $this->state;
    }
    public function cancel(): void
    {
        if ($this->state !== self::PENDING) {
            return;
        }
        $this->wait_fn = $this->wait_list = null;
        if ($this->cancel_fn) {
            $fn = $this->cancel_fn;
            $this->cancel_fn = null;
            try {
                $fn();
            } catch (\Throwable $e) {
                $this->reject($e);
            }
        }
        // Reject the promise only if it wasn't rejected in a then callback.
        /** @psalm-suppress RedundantCondition */
        if ($this->state === self::PENDING) {
            $this->reject(new Cancellation_Exception('Promise has been cancelled'));
        }
    }
    public function resolve($value): void
    {
        $this->settle(self::FULFILLED, $value);
    }
    public function reject($reason): void
    {
        $this->settle(self::REJECTED, $reason);
    }
    private function settle(string $state, $value): void
    {
        if ($this->state !== self::PENDING) {
            // Ignore calls with the same resolution.
            if ($state === $this->state && $value === $this->result) {
                return;
            }
            throw $this->state === $state ? new \LogicException("The promise is already {$state}.") : new \LogicException("Cannot change a {$this->state} promise to {$state}");
        }
        if ($value === $this) {
            throw new \LogicException('Cannot fulfill or reject a promise with itself');
        }
        // Clear out the state of the promise but stash the handlers.
        $this->state = $state;
        $this->result = $value;
        $handlers = $this->handlers;
        $this->handlers = null;
        $this->wait_list = $this->wait_fn = null;
        $this->cancel_fn = null;
        if (!$handlers) {
            return;
        }
        // If the value was not a settled promise or a thenable, then resolve
        // it in the task queue using the correct ID.
        if (!is_object($value) || !method_exists($value, 'then')) {
            $id = $state === self::FULFILLED ? 1 : 2;
            // It's a success, so resolve the handlers in the queue.
            Utils::queue()->add(static function () use ($id, $value, $handlers): void {
                foreach ($handlers as $handler) {
                    self::call_handler($id, $value, $handler);
                }
            });
        } elseif ($value instanceof Promise && Is::pending($value)) {
            // We can just merge our handlers onto the next promise.
            $value->handlers = array_merge($value->handlers, $handlers);
        } else {
            // Resolve the handlers when the forwarded promise is resolved.
            $value->then(static function ($value) use ($handlers): void {
                foreach ($handlers as $handler) {
                    self::call_handler(1, $value, $handler);
                }
            }, static function ($reason) use ($handlers): void {
                foreach ($handlers as $handler) {
                    self::call_handler(2, $reason, $handler);
                }
            });
        }
    }
    /**
     * Call a stack of handlers using a specific callback index and value.
     *
     * @param int   $index   1 (resolve) or 2 (reject).
     * @param mixed $value   Value to pass to the callback.
     * @param array $handler Array of handler data (promise and callbacks).
     */
    private static function call_handler(int $index, $value, array $handler): void
    {
        /** @var PromiseInterface $promise */
        $promise = $handler[0];
        // The promise may have been cancelled or resolved before placing
        // this thunk in the queue.
        if (Is::settled($promise)) {
            return;
        }
        try {
            if (isset($handler[$index])) {
                /*
                 * If $f throws an exception, then $handler will be in the exception
                 * stack trace. Since $handler contains a reference to the callable
                 * itself we get a circular reference. We clear the $handler
                 * here to avoid that memory leak.
                 */
                $f = $handler[$index];
                unset($handler);
                $promise->resolve($f($value));
            } elseif ($index === 1) {
                // Forward resolution values as-is.
                $promise->resolve($value);
            } else {
                // Forward rejections down the chain.
                $promise->reject($value);
            }
        } catch (\Throwable $reason) {
            $promise->reject($reason);
        }
    }
    private function wait_if_pending(): void
    {
        if ($this->state !== self::PENDING) {
            return;
        }
        if ($this->wait_fn) {
            $this->invoke_wait_fn();
        } elseif ($this->wait_list) {
            $this->invoke_wait_list();
        } else {
            // If there's no wait function, then reject the promise.
            $this->reject('Cannot wait on a promise that has ' . 'no internal wait function. You must provide a wait ' . 'function when constructing the promise to be able to ' . 'wait on a promise.');
        }
        Utils::queue()->run();
        /** @psalm-suppress RedundantCondition */
        if ($this->state === self::PENDING) {
            $this->reject('Invoking the wait callback did not resolve the promise');
        }
    }
    private function invoke_wait_fn(): void
    {
        try {
            $wfn = $this->wait_fn;
            $this->wait_fn = null;
            $wfn(true);
        } catch (\Throwable $reason) {
            if ($this->state === self::PENDING) {
                // The promise has not been resolved yet, so reject the promise
                // with the exception.
                $this->reject($reason);
            } else {
                // The promise was already resolved, so there's a problem in
                // the application.
                throw $reason;
            }
        }
    }
    private function invoke_wait_list(): void
    {
        $wait_list = $this->wait_list;
        $this->wait_list = null;
        foreach ($wait_list as $result) {
            do {
                $result->wait_if_pending();
                $result = $result->result;
            } while ($result instanceof Promise);
            if ($result instanceof Promise_Interface) {
                $result->wait(false);
            }
        }
    }
}