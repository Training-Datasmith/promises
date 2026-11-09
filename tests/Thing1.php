<?php

declare(strict_types=1);

namespace Guzzle_Http\Promise\Tests;

class Thing1
{
    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function __toString()
    {
        return $this->message;
    }
}
