<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

/**
 * Interface used with classes that return a promise.
 */
interface Promisor_Interface
{
    /**
     * Returns a promise.
     */
    public function promise(): Promise_Interface;
}