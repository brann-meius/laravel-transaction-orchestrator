<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Support\Services;

use PHPUnit\Event\Code\Throwable;
use stdClass;

/**
 * A simple mockable service used in support controllers for testing.
 * In tests this class can be mocked and configured to return
 * whatever data is required for the scenario.
 */
final class MockableService
{
    /**
     * @throws Throwable
     * @internal Stub method. Should be mocked in tests.
     */
    public function handle(mixed ...$arguments): mixed
    {
        return new stdClass();
    }
}
