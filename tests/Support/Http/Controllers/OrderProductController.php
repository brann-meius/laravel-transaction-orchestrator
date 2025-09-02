<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Support\Http\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\LockForUpdate;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\SharedLock;
use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Models\Order;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Models\Product;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Services\MockableService;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderProductController extends Controller
{
    public function __construct(
        private MockableService $mockableService,
    ) {
        //
    }

    #[Transactional]
    public function store(
        #[LockForUpdate] Order $order,
        #[LockForUpdate] Product $product
    ): Response {
        try {
            $order = $this->mockableService->handle($order, $product);
        } catch (RuntimeException $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable) {
            return response()->json([
                'error' => 'Unable to add product to order.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return JsonResource::make($order)->response();
    }

    #[Transactional]
    public function destroy(
        #[SharedLock] Order $order,
        #[SharedLock] Product $product
    ): Response {
        try {
            $order = $this->mockableService->handle($order, $product);
        } catch (RuntimeException $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable) {
            return response()->json([
                'error' => 'Unable to add product to order.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return JsonResource::make($order)->response();
    }
}
