<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

class SharedLockScope extends LockScope
{
    protected function query(Builder $builder): void
    {
        $builder->sharedLock();
    }
}
