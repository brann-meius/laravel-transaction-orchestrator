# Laravel Transaction Orchestrator

---

[![License](https://img.shields.io/github/license/brann-meius/laravel-transaction-orchestrator)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D%2011.0-orange)](https://laravel.com/)

---

Declarative transactions and row-level locks for Laravel 11+ via PHP attributes.
Annotate controller methods with `#[Transactional]` — get transactions with retries/backoff and HTTP-aware rollback.
Annotate parameters with `#[LockForUpdate]` / `#[SharedLock]` — get row-locks directly during route model binding. Zero boilerplate.

> Internally it uses the **standard Laravel/Eloquent API**: `lockForUpdate()` and `sharedLock()`. Support = whatever Laravel and your DB driver support.

---

## Table of Contents

* [Features](#features)
* [Requirements & DB Support](#requirements--db-support)
* [Installation](#installation)
* [Quick Start](#quick-start)
* [Attribute Reference](#attribute-reference)
    * [`#[Transactional]`](#transactional)
    * [`#[LockForUpdate]` and `#[SharedLock]`](#lockforupdate-and-sharedlock)
* [Retries & Backoff](#retries--backoff)
* [HTTP-Aware Rollback](#http-aware-rollback)
* [Multiple Connections](#multiple-connections)
* [Nested Transactions](#nested-transactions)
* [Limitations](#limitations)
* [License](#license)

---

## Features

* `#[Transactional]` — wrap controller actions in a transaction, optionally with retries and backoff.
* Rollback policy based on HTTP response (4xx/5xx/specific codes).
* Exceptions that **do not** trigger rollback (`noRollbackOn`).
* `#[LockForUpdate]` / `#[SharedLock]` on action parameters — row-lock during route model binding.
* Zero config: service provider and router decoration auto-registered.

---

## Requirements & DB Support

* PHP **8.2+**
* Laravel **11+**

**Row lock support** is fully delegated to Laravel/Eloquent:

* MySQL/MariaDB — `FOR UPDATE` / `FOR SHARE` (or `LOCK IN SHARE MODE` on older versions).
* PostgreSQL — `FOR UPDATE` / `FOR SHARE` (or `FOR KEY SHARE` depending on context).
* SQL Server — via hints (`UPDLOCK`, `ROWLOCK`), same as Laravel does.
* SQLite — no row-level lock for `SELECT` (effectively no-op).

If your driver/version doesn’t support the mode, behavior matches Laravel.

---

## Installation

1. **Composer Installation:**  
   Install the package using Composer:

    ```bash
    composer require meius/laravel-transaction-orchestrator
    ```

2. **Register the Service Provider:**  
   Manually register the service provider by adding it to your `bootstrap/providers.php` file:

```php
return [
    // Other service providers...
    Meius\LaravelTransactionOrchestrator\Providers\TransactionOrchestratorServiceProvider::class,
];
```

---

## Quick Start

```php
use App\Models\Order;
use App\Repositories\OrderRepository;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\LockForUpdate;
use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Enums\HttpRollbackPolicy;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {
        //
    }
    
    #[Transactional(
        connection: 'mysql',
        retries: 3,
        backoff: [50, 100, 200], // milliseconds
        noRollbackOn: [QueryException::class],
        rollbackOnHttpError: HttpRollbackPolicy::ROLLBACK_ON_5XX,
    )]
    public function destroy(#[LockForUpdate] Order $order): Response 
    {
        try {
            $this->orderRepository->delete($order);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Unable to delete the order.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 5xx → rollback (per policy)
        }

        return response()->noContent(); // 204 → commit
    }
}
```

---

## Attribute Reference

### `Transactional`

**Purpose:** run the controller method inside transaction(s).

**Parameters & behavior:**

* `connection`: `null|string|string[]`.
    * `null` → default from `config/database.php`.
    * Normalized to array at runtime → `$connections`.
* `retries`: how many times to retry on **transient** DB errors (deadlock, lock timeout, disconnect, etc.).
* `backoff`: delay in ms before retry.
    * Single number = constant delay.
    * Array = per-attempt delay, last value repeats.
* `noRollbackOn`: list of exception FQCNs that **do not** trigger rollback.
* `rollbackOnHttpError`: rollback policy based on HTTP response:
  `ROLLBACK_NONE`, `ROLLBACK_ON_4XX`, `ROLLBACK_ON_5XX`, `ROLLBACK_ON_4XX_5XX`(default), or list of codes (`[409, 422]`).

**Validation (constructor enforces):**

* `backoff` as array → must be `list<int>`.
* `rollbackOnHttpError` as array → must be `list<int>`.
* `noRollbackOn` → must be `Throwable` subclasses.

**Transaction outcome:**

* Exceptions are **not** swallowed. Any unhandled exception → rollback (unless in `noRollbackOn`).
* If no exception: commit/rollback decided by the **Response** and policy.

---

### `LockForUpdate` and `SharedLock`

**Purpose:** apply row-lock to action parameter during route model binding.

How it works:

* Before resolving the parameter, the router checks the attribute and applies the standard Eloquent lock (`lockForUpdate()` or `sharedLock()`) **just once**.
* Lock does **not** leak into other queries inside the method.
* SQL and semantics depend on your DB driver/version (see [Requirements](#requirements--db-support)).

**Example:**

```php
use App\Exceptions\Products\CannotRemoveProductException;
use App\Exceptions\Products\ProductNotInOrderException;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\LockForUpdate;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\SharedLock;
use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Symfony\Component\HttpFoundation\Response;

class OrderProductController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
        //
    }

    /**
     * Removes a product from the order.
     */
    #[Transactional]
    public function destroy(
        #[LockForUpdate] Order $order,
        #[SharedLock] Product $product
    ): Response {
        try {
            $order = $this->orderService->recalculate($order, $product);
        } catch (ProductNotInOrderException|CannotRemoveProductException $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return response()->json([
                'error' => 'Unable to remove product from order.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return OrderResource::make($order)->response();
    }
}
```

---

## Retries & Backoff

* Enabled when `retries > 0`.
* Transient errors include common concurrency/connection issues.
* `backoff` in ms. Example `[10, 30, 70]` → values applied per attempt, last repeated.

> With retries enabled, make operations **idempotent** (or dedupe-safe). The package does not enforce idempotency.

---

## HTTP-Aware Rollback

Rollback can be triggered by response status without exceptions:

* Validation → `422` → rollback.
* Resource conflict → `409` → rollback.
* Any `5xx` → rollback (default).

Customize via policy or code list:

```php
#[Transactional(rollbackOnHttpError: [409, 422])]
```

Decision is made **after** action returns a `Response`, before sending body.

---

## Multiple Connections

`connection` accepts an array:

```php
#[Transactional(connection: ['mysql', 'pgsql'])]
```

* Opens a transaction for each connection.
* On error/rollback condition — **all** are rolled back.
* This is **not** 2PC (no cross-DB atomicity).

---

## Nested Transactions

* If you call `DB::transaction()` inside, Laravel uses **savepoints** (if supported).
* Outer `#[Transactional]` decides final commit/rollback.
* Do not mix manual `commit()`/`rollBack()` with orchestrator — use `DB::transaction()`.

---

## Limitations

* Lock behavior depends on DB driver/version; package follows Laravel exactly.
* Locks apply **only** during route model binding. Queries inside method are unaffected.
* No 2PC across DBs.
* Retries ≠ idempotency: duplicate side effects are your responsibility.

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
