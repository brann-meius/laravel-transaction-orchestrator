<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Providers;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\ServiceProvider;
use Meius\LaravelTransactionOrchestrator\Decorators\Routing\RouterDecorator;
use Meius\LaravelTransactionOrchestrator\Http\Middleware\LockAwareSubstituteBindings;

class TransactionOrchestratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(LockAwareSubstituteBindings::class)
            ->needs(Registrar::class)
            ->give(RouterDecorator::class);
    }
}
