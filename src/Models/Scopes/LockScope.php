<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Meius\LaravelTransactionOrchestrator\Contracts\Models\Scopes\LockScopeContract;

/**
 * Abstract class LockScope
 *
 * Defines a base behavior for applying a scope to a query,
 * ensuring that it is only applied once.
 */
abstract class LockScope implements LockScopeContract
{
    /**
     * Flag to track whether the scope has already been applied.
     * Prevents applying the same scope multiple times.
     */
    private bool $applied = false;

    /**
     * Defines the actual query modifications for the scope.
     */
    abstract protected function query(Builder $builder): void;

    public function apply(Builder $builder, Model $model): void
    {
        if (!$this->applied) {
            $this->query($builder);
        }

        $this->applied = true;
    }
}
