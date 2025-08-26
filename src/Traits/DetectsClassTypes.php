<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Traits;

trait DetectsClassTypes
{
    /**
     * Returns `true` if $current is an instance of any class in the provided list.
     */
    private function isInstanceOfAny(object $current, array $classes): bool
    {
        foreach ($classes as $class) {
            if ($current instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
