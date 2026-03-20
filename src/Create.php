<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

final class Create
{
    /**
     * Creates a promise for a value if the value is not a promise.
     *
     * @param mixed $value Promise or value.
     */
    public static function promise_for($value): Promise_Interface
    {
        if ($value instanceof Promise_Interface) {
            return $value;
        }
        // Return a Guzzle promise that shadows the given promise.
        if (is_object($value) && method_exists($value, 'then')) {
            $wfn = method_exists($value, 'wait') ? [$value, 'wait'] : null;
            $cfn = method_exists($value, 'cancel') ? [$value, 'cancel'] : null;
            $promise = new Promise($wfn, $cfn);
            $value->then([$promise, 'resolve'], [$promise, 'reject']);
            return $promise;
        }
        return new Fulfilled_Promise($value);
    }
    /**
     * Creates a rejected promise for a reason if the reason is not a promise.
     * If the provided reason is a promise, then it is returned as-is.
     *
     * @param mixed $reason Promise or reason.
     */
    public static function rejection_for($reason): Promise_Interface
    {
        if ($reason instanceof Promise_Interface) {
            return $reason;
        }
        return new Rejected_Promise($reason);
    }
    /**
     * Create an exception for a rejected promise value.
     *
     * @param mixed $reason
     */
    public static function exception_for($reason): \Throwable
    {
        if ($reason instanceof \Throwable) {
            return $reason;
        }
        return new Rejection_Exception($reason);
    }
    /**
     * Returns an iterator for the given value.
     *
     * @param mixed $value
     */
    public static function iter_for($value): \Iterator
    {
        if ($value instanceof \Iterator) {
            return $value;
        }
        if (is_array($value)) {
            return new \ArrayIterator($value);
        }
        return new \ArrayIterator([$value]);
    }
}