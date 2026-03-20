<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

/**
 * A promise that has been rejected.
 *
 * Thenning off of this promise will invoke the onRejected callback
 * immediately and ignore other callbacks.
 *
 * @final
 */
class Rejected_Promise implements Promise_Interface
{
    private $reason;
    /**
     * @param mixed $reason
     */
    public function __construct($reason)
    {
        if (is_object($reason) && method_exists($reason, 'then')) {
            throw new \InvalidArgumentException('You cannot create a RejectedPromise with a promise.');
        }
        $this->reason = $reason;
    }
    public function then(?callable $on_fulfilled = null, ?callable $on_rejected = null): Promise_Interface
    {
        // If there's no onRejected callback then just return self.
        if (!$on_rejected) {
            return $this;
        }
        $queue = Utils::queue();
        $reason = $this->reason;
        $p = new Promise([$queue, 'run']);
        $queue->add(static function () use ($p, $reason, $on_rejected): void {
            if (Is::pending($p)) {
                try {
                    // Return a resolved promise if onRejected does not throw.
                    $p->resolve($on_rejected($reason));
                } catch (\Throwable $e) {
                    // onRejected threw, so return a rejected promise.
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
        if ($unwrap) {
            throw Create::exception_for($this->reason);
        }
        return null;
    }
    public function get_state(): string
    {
        return self::REJECTED;
    }
    public function resolve($value): void
    {
        throw new \LogicException('Cannot resolve a rejected promise');
    }
    public function reject($reason): void
    {
        if ($reason !== $this->reason) {
            throw new \LogicException('Cannot reject a rejected promise');
        }
    }
    public function cancel(): void
    {
        // pass
    }
}