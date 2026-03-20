<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

/**
 * Exception that is set as the reason for a promise that has been cancelled.
 */
class Cancellation_Exception extends Rejection_Exception
{
}