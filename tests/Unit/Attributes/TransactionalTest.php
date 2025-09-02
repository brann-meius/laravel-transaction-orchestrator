<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Attributes;

use Attribute;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Enums\HttpRollbackPolicy;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Http\Controllers\OrderProductController;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TransactionalTest extends TestCase
{
    public function testItIsDeclaredAsAttribute(): void
    {
        $reflection = new ReflectionClass(Transactional::class);
        $this->assertNotEmpty(
            $reflection->getAttributes(Attribute::class),
            'Transactional must be declared as a PHP Attribute (#[Attribute]).'
        );
    }

    public function testDefaultConnectionIsTakenFromConfigIfNullGiven(): void
    {
        $transactional = new Transactional(null);
        $this->assertSame(['testing'], $transactional->connections);
    }

    public function testConnectionsAreNormalizedToArray(): void
    {
        $single = new Transactional('mysql');
        $this->assertSame(['mysql'], $single->connections, 'Single string must normalize to array.');

        $multi = new Transactional(['mysql', 'pgsql']);
        $this->assertSame(['mysql', 'pgsql'], $multi->connections, 'Array of strings must remain unchanged.');
    }

    #[DataProvider('validConstructorCases')]
    public function testValidConstructorCases(
        null|string|array $connection,
        int $retries,
        int|array $backoff,
        array $noRollbackOn = [],
        HttpRollbackPolicy|array $rollbackOnHttpError = HttpRollbackPolicy::ROLLBACK_ON_5XX,
        ?array $expectedConnections = null
    ): void {
        $transactional = new Transactional(
            $connection,
            $retries,
            $backoff,
            $noRollbackOn,
            $rollbackOnHttpError
        );

        if ($expectedConnections !== null) {
            $this->assertSame($expectedConnections, $transactional->connections);
        }

        $this->assertSame($retries, $transactional->retries);
        $this->assertSame($backoff, $transactional->backoff);
        $this->assertSame($noRollbackOn, $transactional->noRollbackOn);
        $this->assertSame($rollbackOnHttpError, $transactional->rollbackOnHttpError);
    }

    #[DataProvider('invalidConstructorCases')]
    public function testInvalidConstructorCases(
        null|string|array $connection,
        int $retries,
        int|array $backoff,
        array $noRollbackOn = [],
        HttpRollbackPolicy|array $rollbackOnHttpError = HttpRollbackPolicy::ROLLBACK_ON_5XX
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new Transactional(
            $connection,
            $retries,
            $backoff,
            $noRollbackOn,
            $rollbackOnHttpError
        );
    }

    /**
     * Positive test cases: valid constructor arguments.
     */
    public static function validConstructorCases(): iterable
    {
        yield 'null connection -> uses Config, int backoff' => [null, 1, 50];
        yield 'single connection name' => ['mysql', 2, 100, [], HttpRollbackPolicy::ROLLBACK_NONE, ['mysql']];
        yield 'multiple connections' => [['mysql', 'pgsql'], 0, 10, [], HttpRollbackPolicy::ROLLBACK_ON_5XX, ['mysql', 'pgsql']];

        // backoff (int)
        yield 'zero backoff (int)' => ['mysql', 1, 0];
        yield 'large backoff (int)' => ['mysql', 1, 60_000];

        // backoff (array)
        yield 'empty backoff list' => ['mysql', 3, []];
        yield 'backoff list shorter than retries' => ['mysql', 5, [10, 20]];
        yield 'backoff list longer than retries' => ['mysql', 1, [10, 20, 30, 40]];

        // noRollbackOn
        yield 'noRollbackOn with custom throwable' => ['mysql', 1, 10, [QueryException::class]];
        yield 'noRollbackOn with multiple throwables' => ['mysql', 1, 10, [DeadlockException::class, QueryException::class]];
        yield 'noRollbackOn accepts Throwable base class' => ['mysql', 1, 10, [Throwable::class]];

        // rollbackOnHttpError (all enum variants + array)
        yield 'rollback none' => ['mysql', 1, 10, [], HttpRollbackPolicy::ROLLBACK_NONE];
        yield 'rollback on 4xx' => ['mysql', 1, 10, [], HttpRollbackPolicy::ROLLBACK_ON_4XX];
        yield 'rollback on 5xx' => ['mysql', 1, 10, [], HttpRollbackPolicy::ROLLBACK_ON_5XX];
        yield 'rollback on 4xx and 5xx' => ['mysql', 1, 10, [], HttpRollbackPolicy::ROLLBACK_ON_4XX_5XX];
        yield 'rollback on specific codes (valid ints)' => ['mysql', 1, 10, [], [Response::HTTP_INTERNAL_SERVER_ERROR, Response::HTTP_UNPROCESSABLE_ENTITY]];
        yield 'rollback on arbitrary ints (still allowed)' => ['mysql', 1, 10, [], [99, 600, -1]];

        // retries
        yield 'zero retries' => ['mysql', 0, 10];
        yield 'negative retries (currently not validated)' => ['mysql', -1, 10];
    }

    /**
     * Negative test cases: invalid constructor arguments.
     */
    public static function invalidConstructorCases(): iterable
    {
        // backoff
        yield 'backoff must be list (associative given)' => [null, 1, ['a' => 10]];
        yield 'backoff must contain only ints (string found)' => [null, 1, [10, '20']];
        yield 'backoff must contain only ints (float found)' => [null, 1, [10, 20.5]];
        yield 'backoff must contain only ints (bool found)' => [null, 1, [true, 10]];

        // rollbackOnHttpError
        yield 'rollbackOnHttpError must be list (associative given)' => [null, 1, 50, [], ['x' => 500]];
        yield 'rollbackOnHttpError must contain only ints (string found)' => [null, 1, 50, [], ['500']];
        yield 'rollbackOnHttpError must contain only ints (float found)' => [null, 1, 50, [], [500.0]];
        yield 'rollbackOnHttpError must contain only ints (bool found)' => [null, 1, 50, [], [false, 500]];

        // noRollbackOn
        yield 'noRollbackOn contains non-Throwable class' => [null, 1, 50, [OrderProductController::class]];
        yield 'noRollbackOn contains non-existing class-string' => [null, 1, 50, ['\\Definitely\\Not\\A\\Class']];
    }
}
