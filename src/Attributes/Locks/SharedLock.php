<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Attributes\Locks;

use Attribute;
use Meius\LaravelTransactionOrchestrator\Contracts\Attributes\Locks\LockAttributeContract;

/**
 * Marks a parameter of a controller method for a shared row-level lock.
 *
 * When applied, the corresponding database row will be locked using `LOCK IN SHARE MODE`,
 * allowing concurrent reads but blocking writes until the current transaction ends.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class SharedLock implements LockAttributeContract
{
    //
}
