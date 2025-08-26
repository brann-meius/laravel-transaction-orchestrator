<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Http\Middleware;

use Carbon\CarbonInterval;
use Closure;
use Illuminate\Database\DetectsConcurrencyErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Sleep;
use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Support\Database\TransactionManager;
use Meius\LaravelTransactionOrchestrator\Support\Reflection\MethodAttributeInspector;
use Meius\LaravelTransactionOrchestrator\Traits\DetectsClassTypes;
use Meius\LaravelTransactionOrchestrator\Traits\DeterminesHttpRollback;
use Throwable;

readonly class TransactionalMiddleware
{
    use DetectsClassTypes;
    use DetectsConcurrencyErrors;
    use DeterminesHttpRollback;

    public function __construct(
        private TransactionManager $transactionManager,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $route = $request->route();
        $inspector = MethodAttributeInspector::for(
            $route->getControllerClass(),
            $route->getActionMethod()
        );

        if ($inspector->doesntHave(Transactional::class)) {
            return $next($request);
        }

        /** @var Transactional $transactional */
        $transactional = $inspector->first(Transactional::class)->newInstance();

        return $this->wrapWithTransaction($request, $next, $transactional);
    }

    /**
     * Executes the controller action within a transactional block.
     *
     * @throws Throwable
     */
    protected function wrapWithTransaction(Request $request, Closure $next, Transactional $transactional): mixed
    {
        $attempt = 0;
        $this->transactionManager->initializeConnectionsFrom($transactional);

        do {
            $this->transactionManager->beginTransactions();

            try {
                $response = $next($request);
                $this->shouldRollbackOnHttpError($transactional, $response)
                    ? $this->transactionManager->rollbackTransactions()
                    : $this->transactionManager->commitTransactions();

                break;
            } catch (Throwable $exception) {
                if ($this->isInstanceOfAny($exception, $transactional->noRollbackOn)) {
                    try {
                        $this->transactionManager->commitTransactions();
                    } catch (Throwable $exception) {
                        // Commit failure overrides the original exception.
                        continue;
                    }

                    throw $exception;
                }
            }
        } while ($this->handleException($exception, $transactional, $attempt++));

        return $response;
    }

    /**
     * Handles a failed transaction attempt by rolling back and determining whether to retry.
     *
     * @throws Throwable
     */
    protected function handleException(Throwable $exception, Transactional $transactional, int $attempt): true
    {
        $this->transactionManager->rollbackTransactions();

        if (!$this->causedByConcurrencyError($exception) || $attempt >= $transactional->retries) {
            throw $exception;
        }

        $this->sleepBackoff($transactional->backoff, $attempt);

        return true;
    }

    /**
     * Applies a retry backoff delay.
     */
    private function sleepBackoff(int|array $backoff, int $attempt): void
    {
        $milliseconds = match (true) {
            is_int($backoff) => $backoff,
            $attempt < count($backoff) => $backoff[$attempt],
            default => end($backoff),
        };

        Sleep::for(CarbonInterval::milliseconds($milliseconds));
    }
}
