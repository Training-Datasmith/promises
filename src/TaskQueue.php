<?php

declare (strict_types=1);
namespace Guzzle_Http\Promise;

/**
 * A task queue that executes tasks in a FIFO order.
 *
 * This task queue class is used to settle promises asynchronously and
 * maintains a constant stack size. You can use the task queue asynchronously
 * by calling the `run()` function of the global task queue in an event loop.
 *
 *     GuzzleHttp\Promise\Utils::queue()->run();
 *
 * @final
 */
class Task_Queue implements Task_Queue_Interface
{
    private $enable_shutdown = true;
    private $queue = [];
    public function __construct(bool $with_shutdown = true)
    {
        if ($with_shutdown) {
            register_shutdown_function(function (): void {
                if ($this->enable_shutdown) {
                    // Only run the tasks if an E_ERROR didn't occur.
                    $err = error_get_last();
                    if (!$err || $err['type'] ^ E_ERROR) {
                        $this->run();
                    }
                }
            });
        }
    }
    public function is_empty(): bool
    {
        return !$this->queue;
    }
    public function add(callable $task): void
    {
        $this->queue[] = $task;
    }
    public function run(): void
    {
        while ($task = array_shift($this->queue)) {
            /** @var callable $task */
            $task();
        }
    }
    /**
     * The task queue will be run and exhausted by default when the process
     * exits IFF the exit is not the result of a PHP E_ERROR error.
     *
     * You can disable running the automatic shutdown of the queue by calling
     * this function. If you disable the task queue shutdown process, then you
     * MUST either run the task queue (as a result of running your event loop
     * or manually using the run() method) or wait on each outstanding promise.
     *
     * Note: This shutdown will occur before any destructors are triggered.
     */
    public function disable_shutdown(): void
    {
        $this->enable_shutdown = false;
    }
}