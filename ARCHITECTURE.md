# Architecture: guzzlehttp/promises

## Purpose

A Promises/A+ implementation for PHP, used primarily by Guzzle HTTP client. Provides
asynchronous operation chaining without requiring coroutines or fibers, using a
synchronous task queue that is flushed between promise resolution steps.

## Directory Structure

```
src/
  Promise_Interface.php   # Contract: then(), otherwise(), resolve(), reject(), cancel(), wait()
  Promise.php             # Core Promises/A+ implementation (non-recursive resolution)
  Fulfilled_Promise.php   # Immutable already-fulfilled promise
  Rejected_Promise.php    # Immutable already-rejected promise
  Promisor_Interface.php  # Interface for objects that own a promise
  Aggregate_Exception.php # Exception aggregating multiple rejection reasons
  Cancellation_Exception.php  # Thrown when a promise is cancelled
  Rejection_Exception.php     # Wraps a rejection reason in a throwable
  Coroutine.php           # Generator-based coroutine for async-like sequential code
  Each.php                # Utilities for processing iterables of promises
  Each_Promise.php        # Promise that resolves when a set of promises complete
  Create.php              # Factory: creates promises from values, iterators, or exceptions
  Is.php                  # Type predicates: isPending, isFulfilled, isRejected, isCancelled
  Task_Queue.php          # Global FIFO queue for deferred microtask-style callbacks
  Task_Queue_Interface.php  # Contract for the task queue
  Utils.php               # Higher-level utilities: all(), any(), settle(), unwrap(), etc.
```

## Key Design Decisions

### Non-Recursive Resolution via Task Queue

Promise resolution chains are not processed recursively to avoid stack overflow on deep
chains. Instead, handler callbacks are enqueued in a global `Task_Queue` and processed
iteratively. The queue is flushed whenever `wait()` is called or a promise settles.

### Synchronous Wait

Promises can be synchronously awaited via `wait(bool $unwrap = true)`. If the promise
has a wait function (provided at construction), it is invoked to drive resolution.
Without a wait function, the promise must already be settled or resolution must be
triggered by the task queue.

### Three Concrete States

`Fulfilled_Promise` and `Rejected_Promise` are immutable and bypass the state machine
entirely. They are returned by `Create::promise_for()` and `Create::rejection_for()`
when a value is already known, avoiding overhead of the full `Promise` class.

## Extension Points

- **`Task_Queue_Interface`** — replace the global task queue with a custom implementation.
- **`Promise_Interface`** — implement to integrate with event loops or other async systems.
- **`Promisor_Interface`** — implement for objects that manage their own promise lifecycle.

## Dependency Flow

```
Create::promise_for($value)
  └─ Fulfilled_Promise (if scalar) or Promise (if callable/promise)

Promise::then(onFulfilled, onRejected)
  └─ New Promise with handlers registered
       └─ Task_Queue::run() when parent settles

Utils::all([$p1, $p2, ...])
  └─ Each_Promise that fulfills when all input promises fulfill
       └─ Each::of_limit() for concurrency control
```
