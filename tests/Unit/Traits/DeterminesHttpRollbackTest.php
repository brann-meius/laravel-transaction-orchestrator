<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Traits;

use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Enums\HttpRollbackPolicy;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;
use Meius\LaravelTransactionOrchestrator\Traits\DeterminesHttpRollback;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

class DeterminesHttpRollbackTest  extends TestCase
{
    use DeterminesHttpRollback;

    #[DataProvider('cases')]
    public function testRollbackScenarios(HttpRollbackPolicy|array $policy, int $status, bool $expected): void
    {
        $transactional = new Transactional(rollbackOnHttpError: $policy);

        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('isClientError')->willReturn($status >= 400 && $status < 500);
        $response->method('isServerError')->willReturn($status >= 500 && $status < 600);

        $this->assertSame($expected, $this->shouldRollbackOnHttpError($transactional, $response));
    }

    public static function cases(): array
    {
        return [
            // ROLLBACK_NONE
            'none 200' => [HttpRollbackPolicy::ROLLBACK_NONE, Response::HTTP_OK, false],
            'none 500' => [HttpRollbackPolicy::ROLLBACK_NONE, Response::HTTP_INTERNAL_SERVER_ERROR, false],

            // ROLLBACK_ON_4XX
            '4xx 404' => [HttpRollbackPolicy::ROLLBACK_ON_4XX, Response::HTTP_NOT_FOUND, true],
            '4xx 500' => [HttpRollbackPolicy::ROLLBACK_ON_4XX, Response::HTTP_INTERNAL_SERVER_ERROR, false],

            // ROLLBACK_ON_5XX
            '5xx 500' => [HttpRollbackPolicy::ROLLBACK_ON_5XX, Response::HTTP_INTERNAL_SERVER_ERROR, true],
            '5xx 404' => [HttpRollbackPolicy::ROLLBACK_ON_5XX, Response::HTTP_NOT_FOUND, false],

            // ROLLBACK_ON_4XX_5XX
            '4xx5xx 404' => [HttpRollbackPolicy::ROLLBACK_ON_4XX_5XX, Response::HTTP_NOT_FOUND, true],
            '4xx5xx 500' => [HttpRollbackPolicy::ROLLBACK_ON_4XX_5XX, Response::HTTP_INTERNAL_SERVER_ERROR, true],
            '4xx5xx 200' => [HttpRollbackPolicy::ROLLBACK_ON_4XX_5XX, Response::HTTP_OK, false],

            // Specific codes
            'array contains 418' => [[
                Response::HTTP_I_AM_A_TEAPOT,
                Response::HTTP_TOO_MANY_REQUESTS
            ], Response::HTTP_I_AM_A_TEAPOT, true],
            'array contains 429' => [[
                Response::HTTP_I_AM_A_TEAPOT,
                Response::HTTP_TOO_MANY_REQUESTS
            ], Response::HTTP_TOO_MANY_REQUESTS, true],
            'array missing code' => [[
                Response::HTTP_I_AM_A_TEAPOT,
                Response::HTTP_TOO_MANY_REQUESTS
            ], Response::HTTP_INTERNAL_SERVER_ERROR, false],
        ];
    }
}
