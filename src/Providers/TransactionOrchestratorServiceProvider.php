<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Providers;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\ServiceProvider;
use Meius\LaravelTransactionOrchestrator\Decorators\Routing\RouterDecorator;
use Meius\LaravelTransactionOrchestrator\Http\Middleware\LockAwareSubstituteBindings;
use Meius\LaravelTransactionOrchestrator\Http\Middleware\TransactionalMiddleware;

class TransactionOrchestratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(LockAwareSubstituteBindings::class)
            ->needs(Registrar::class)
            ->give(RouterDecorator::class);
    }

    public function boot(): void
    {
        $this->app->afterResolving(Middleware::class, function (Middleware $middleware) {
            $middleware->priority([
                TransactionalMiddleware::class,
                LockAwareSubstituteBindings::class,
                SubstituteBindings::class,
            ])->append([
                TransactionalMiddleware::class,
                LockAwareSubstituteBindings::class,
            ]);
        });
    }
}
