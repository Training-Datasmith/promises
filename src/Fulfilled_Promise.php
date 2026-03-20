<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

/**
 * A promise that has been fulfilled.
 *
 * Thenning off of this promise will invoke the onFulfilled callback
 * immediately and ignore other callbacks.
 *
 * @final
 */
class Fulfilled_Promise implements Promise_Interface
{
    private $value;
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        if (is_object($value) && method_exists($value, 'then')) {
            throw new \InvalidArgumentException('You cannot create a FulfilledPromise with a promise.');
        }
        $this->value = $value;
    }
    public function then(?callable $on_fulfilled = null, ?callable $on_rejected = null): Promise_Interface
    {
        // Return itself if there is no onFulfilled function.
        if (!$on_fulfilled) {
            return $this;
        }
        $queue = Utils::queue();
        $p = new Promise([$queue, 'run']);
        $value = $this->value;
        $queue->add(static function () use ($p, $value, $on_fulfilled): void {
            if (Is::pending($p)) {
                try {
                    $p->resolve($on_fulfilled($value));
                } catch (\Throwable $e) {
                    $p->reject($e);
                }
            }
        });
        return $p;
    }
    public function otherwise(callable $on_rejected): Promise_Interface
    {
        return $this->then(null, $on_rejected);
    }
    public function wait(bool $unwrap = true)
    {
        return $unwrap ? $this->value : null;
    }
    public function get_state(): string
    {
        return self::FULFILLED;
    }
    public function resolve($value): void
    {
        if ($value !== $this->value) {
            throw new \LogicException('Cannot resolve a fulfilled promise');
        }
    }
    public function reject($reason): void
    {
        throw new \LogicException('Cannot reject a fulfilled promise');
    }
    public function cancel(): void
    {
        // pass
    }
}