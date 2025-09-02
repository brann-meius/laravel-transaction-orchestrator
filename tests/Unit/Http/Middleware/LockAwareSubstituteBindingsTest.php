<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Http\Middleware;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Meius\LaravelTransactionOrchestrator\Http\Middleware\LockAwareSubstituteBindings;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;

class LockAwareSubstituteBindingsTest extends TestCase
{
    public function testMiddlewareExtendsSubstituteBindingsAndIsResolvable(): void
    {
        $instance = $this->app->make(LockAwareSubstituteBindings::class);

        $this->assertInstanceOf(SubstituteBindings::class, $instance);
    }
}