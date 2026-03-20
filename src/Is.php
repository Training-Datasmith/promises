<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

final class Is
{
    /**
     * Returns true if a promise is pending.
     */
    public static function pending(Promise_Interface $promise): bool
    {
        return $promise->get_state() === Promise_Interface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled or rejected.
     */
    public static function settled(Promise_Interface $promise): bool
    {
        return $promise->get_state() !== Promise_Interface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled.
     */
    public static function fulfilled(Promise_Interface $promise): bool
    {
        return $promise->get_state() === Promise_Interface::FULFILLED;
    }
    /**
     * Returns true if a promise is rejected.
     */
    public static function rejected(Promise_Interface $promise): bool
    {
        return $promise->get_state() === Promise_Interface::REJECTED;
    }
}