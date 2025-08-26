<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Attributes\Locks;

use Attribute;
use Meius\LaravelTransactionOrchestrator\Contracts\Attributes\Locks\LockAttributeContract;

/**
 * Marks a parameter of a controller method for a row-level lock.
 *
 * When applied, the corresponding database row will be locked using `FOR UPDATE` clause,
 * preventing other transactions from modifying or acquiring an exclusive lock until the current transaction completes.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class LockForUpdate implements LockAttributeContract
{
    //
}
