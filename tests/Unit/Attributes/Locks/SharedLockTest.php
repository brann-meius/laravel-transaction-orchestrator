<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Attributes\Locks;

use Meius\LaravelTransactionOrchestrator\Attributes\Locks\SharedLock;
use Meius\LaravelTransactionOrchestrator\Contracts\Attributes\Locks\LockAttributeContract;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;

class SharedLockTest extends TestCase
{
    public function testIsAttributeInstanceOfContract(): void
    {
        $this->assertInstanceOf(LockAttributeContract::class, new SharedLock());
    }
}
