<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

class LockForUpdateScope extends LockScope
{
    protected function query(Builder $builder): void
    {
        $builder->lockForUpdate();
    }
}
