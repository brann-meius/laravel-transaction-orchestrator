<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Models\Scopes;

use Meius\LaravelTransactionOrchestrator\Models\Scopes\LockForUpdateScope;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Models\Order;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;

class LockForUpdateScopeTest extends TestCase
{
    protected string $connection = 'mysql';

    public function testApplyLockToQueryOnlyOnce(): void
    {
        Order::addGlobalScope(new LockForUpdateScope);

        $this->assertStringContainsString('for update', Order::query()->toSql());
        $this->assertStringNotContainsString('for update', Order::query()->toSql());
    }
}
