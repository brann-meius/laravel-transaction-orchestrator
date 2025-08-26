<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Contracts\Attributes\Locks;

/**
 * Marker interface for lock attributes.
 *
 * Used to identify attributes applied to controller method parameters
 * that manage database row-level locking.
 */
interface LockAttributeContract
{
    //
}
