<?php

declare(strict_types=1);

$denylist = [
    'GuzzleHttp',
    'AggregateException',
    'CancellationException',
    'EachPromise',
    'FulfilledPromise',
    'PromiseInterface',
    'PromisorInterface',
    'RejectedPromise',
    'RejectionException',
    'TaskQueueInterface',
    'TaskQueue',
    'promiseFor',
    'rejectionFor',
    'exceptionFor',
    'iterFor',
    'getState',
    'getReason',
    'ofLimitAll',
    'ofLimit',
    'inspectAll',
    'isEmpty',
    'disableShutdown',
    'nextCoroutine',
    '_handleSuccess',
    '_handleFailure',
    'waitIfPending',
    'invokeWaitFn',
    'invokeWaitList',
    'callHandler',
    'createPromise',
    'refillPending',
    'addPending',
    'advanceIterator',
    'checkIfFinished',
    'onFulfilled',
    'onRejected',
    'currentPromise',
    'nextPendingIndex',
    'clearFn',
    'cancelFn',
    'waitFn',
    'waitList',
    'enableShutdown',
    'nextYield',
];

$root = dirname(__DIR__);
$paths = [
    $root . '/src',
    $root . '/tests',
    $root . '/examples',
    $root . '/README.md',
    $root . '/ARCHITECTURE.md',
    $root . '/composer.json',
    $root . '/phpunit.xml.dist',
    $root . '/phpstan.neon.dist',
    $root . '/phpstan-baseline.neon',
    $root . '/Makefile',
    $root . '/.github',
];

$failed = false;

foreach ($paths as $path) {
    if (is_file($path)) {
        $files = [$path];
    } elseif (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $files = [];
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if (substr($name, -4) === '.php' || substr($name, -3) === '.md' || substr($name, -5) === '.neon' || substr($name, -4) === '.yml' || substr($name, -4) === '.xml' || $name === 'Makefile' || $name === 'composer.json') {
                $files[] = (string) $file->getPathname();
            }
        }
    } else {
        continue;
    }

    foreach ($files as $file) {
        $contents = file_get_contents($file);
        if ($contents === false) {
            continue;
        }
        foreach ($denylist as $token) {
            $pattern = '/\b' . preg_quote($token, '/') . '\b/';
            if (preg_match($pattern, $contents)) {
                fwrite(STDERR, sprintf("check-rename-complete: found old name %s in %s\n", $token, $file));
                $failed = true;
            }
        }
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "check-rename-complete: no old repo-owned names remain.\n");
exit(0);
