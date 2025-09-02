<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Meius\LaravelTransactionOrchestrator\Providers\TransactionOrchestratorServiceProvider;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Http\Controllers\OrderProductController;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Http\Controllers\ProductController;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $connection = 'testing';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(TransactionOrchestratorServiceProvider::class);
        Config::set('database.default', $this->connection);
        $this->registerRoutes();
    }

    protected function registerRoutes(): self
    {
        Route::prefix('test')
            ->group(function (): void {

                Route::as('orders.')
                    ->prefix('orders')
                    ->group(function (): void {
                        Route::as('products.')
                            ->prefix('{order}/products')
                            ->group(function (): void {
                                Route::post('{product}', [OrderProductController::class, 'store'])->name('store');
                                Route::delete('{product}', [OrderProductController::class, 'destroy'])->name('destroy');
                            });
                    });

                Route::as('products.')
                    ->prefix('products')
                    ->group(function (): void {
                        Route::get('/', [ProductController::class, 'index'])->name('index');
                        Route::prefix('{product}')
                            ->group(function (): void {
                                Route::put('/', [ProductController::class, 'update'])->name('update');
                                Route::delete('/', [ProductController::class, 'destroy'])->name('destroy');
                            });
                    });

            });

        return $this;
    }
}
