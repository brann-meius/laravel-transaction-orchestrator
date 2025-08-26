<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Attributes;

use Attribute;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Meius\LaravelTransactionOrchestrator\Enums\HttpRollbackPolicy;
use Throwable;

/**
 * Declares that a controller method should be executed within a database transaction.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Transactional
{
    /**
     * Transaction configuration options.
     *
     * @param null|string|string[] $connection Database connection name(s).
     * @param int $retries Number of attempts to retry the transaction in case of transient failures.
     * @param int|int[] $backoff Delay before retrying a failed transaction, in milliseconds.
     *        - Integer: constant delay for each attempt.
     *        - Array: per-attempt delays (e.g., [10, 30, 70]). If fewer values than $retries, the last value is repeated.
     * @param list<class-string<Throwable>> $noRollbackOn Exceptions that should not trigger a rollback.
     * @param HttpRollbackPolicy|int[] $rollbackOnHttpError Rollback policy for HTTP error responses.
     *        - `HttpRollbackPolicy::ROLLBACK_NONE`: never rollback,
     *        - `HttpRollbackPolicy::ROLLBACK_ON_4XX`: rollback on all 4xx responses,
     *        - `HttpRollbackPolicy::ROLLBACK_ON_5XX`: rollback on all 5xx responses,
     *        - `HttpRollbackPolicy::ROLLBACK_ON_4XX_5XX`: rollback on all 4xx/5xx responses,
     *        - `array`: HTTP status codes that trigger rollback.
     */
    public function __construct(
        null|string|array $connection = null,
        public int $retries = 0,
        public int|array $backoff = 10,
        public array $noRollbackOn = [],
        public HttpRollbackPolicy|array $rollbackOnHttpError = HttpRollbackPolicy::ROLLBACK_ON_5XX
    ) {
        if ($connection === null) {
            $connection = [Config::get('database.default')];
        }

        if (!is_array($connection)) {
            $connection = [$connection];
        }

        $this->connections = $connection;

        if (is_array($backoff)) {
            $this->validateIsArrayOfInts($backoff, 'backoff');
        }

        if (is_array($rollbackOnHttpError)) {
            $this->validateIsArrayOfInts($rollbackOnHttpError, 'rollbackOnHttpError');
        }

        $this->validateExceptions($noRollbackOn);
    }

    public array $connections;

    /**
     * @param list<class-string<Throwable>> $exceptions
     */
    private function validateExceptions(array $exceptions): void
    {
        foreach ($exceptions as $exception) {
            if (!is_a($exception, Throwable::class, true)) {
                throw new InvalidArgumentException(
                    sprintf('"%s" is not a valid Throwable class.', $exception)
                );
            }
        }
    }

    private function validateIsArrayOfInts(array $values, string $name): void
    {
        if (!array_is_list($values)) {
            throw new InvalidArgumentException(
                sprintf('%s must be a list.', $name)
            );
        }

        foreach ($values as $value) {
            if (!is_int($value)) {
                throw new InvalidArgumentException(
                    sprintf('%s must be a list of integers, %s given.', $name, gettype($value))
                );
            }
        }
    }
}
