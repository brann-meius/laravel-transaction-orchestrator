<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Models\Scopes;

use Meius\LaravelTransactionOrchestrator\Models\Scopes\SharedLockScope;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Models\Order;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;

class SharedLockScopeTest extends TestCase
{
    protected string $connection = 'mysql';

    public function testApplyLockToQueryOnlyOnce(): void
    {
        Order::addGlobalScope(new SharedLockScope);

        $this->assertStringContainsString('lock in share mode', Order::query()->toSql());
        $this->assertStringNotContainsString('lock in share mode', Order::query()->toSql());
    }
}
