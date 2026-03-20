<?php

declare(strict_types=1);

/**
 * Example: Using guzzlehttp/promises for async-like code in PHP.
 *
 * Install:
 *   composer require guzzlehttp/promises
 *
 * Promises are primarily used with Guzzle's async HTTP requests, but can also be
 * used standalone for any deferred or asynchronous computation.
 */

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Promise\Create;

// --- Create and resolve a promise manually ---

$promise = new Promise();

$promise->then(
    function (string $value): void {
        echo "Fulfilled with: $value\n";
    },
    function (\Throwable $reason): void {
        echo "Rejected: {$reason->getMessage()}\n";
    }
);

$promise->resolve('Hello, World!');
// Output: Fulfilled with: Hello, World!


// --- Chain promises ---

$result = Create::promiseFor('42')
    ->then(fn(string $v): int => (int) $v)
    ->then(fn(int $n): int => $n * 2);

echo $result->wait(); // 84


// --- Reject a promise ---

$rejected = new Promise();
$rejected->then(null, function (\Throwable $e): string {
    return 'recovered: ' . $e->getMessage();
});
$rejected->reject(new \RuntimeException('Something went wrong'));


// --- Wait for multiple promises (all must fulfill) ---

$promises = [
    'a' => Create::promiseFor(1),
    'b' => Create::promiseFor(2),
    'c' => Create::promiseFor(3),
];

$results = Utils::all($promises)->wait();
// $results = ['a' => 1, 'b' => 2, 'c' => 3]


// --- Settle (get results regardless of fulfillment or rejection) ---

$mixed = [
    Create::promiseFor('ok'),
    Create::rejectionFor(new \RuntimeException('fail')),
];

$settled = Utils::settle($mixed)->wait();
// $settled[0] = ['state' => 'fulfilled', 'value' => 'ok']
// $settled[1] = ['state' => 'rejected',  'reason' => RuntimeException]
