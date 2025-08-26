<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Traits;

use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Enums\HttpRollbackPolicy;
use Symfony\Component\HttpFoundation\Response;

trait DeterminesHttpRollback
{
    /**
     * Decides whether to roll back based on HTTP status semantics.
     */
    protected function shouldRollbackOnHttpError(Transactional $transactional, Response $response): bool
    {
        return match ($transactional->rollbackOnHttpError) {
            HttpRollbackPolicy::ROLLBACK_NONE => false,
            HttpRollbackPolicy::ROLLBACK_ON_4XX => $response->isClientError(),
            HttpRollbackPolicy::ROLLBACK_ON_5XX => $response->isServerError(),
            HttpRollbackPolicy::ROLLBACK_ON_4XX_5XX => $response->isClientError() || $response->isServerError(),
            default => in_array($response->getStatusCode(), $transactional->rollbackOnHttpError, true),
        };
    }
}
