<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Support\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\LockForUpdate;
use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Models\Product;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Services\MockableService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private MockableService $mockableService,
    ) {
        //
    }

    public function index(): Response
    {
        return JsonResource::collection(
            $this->mockableService->handle()
        )->response();
    }

    #[Transactional]
    public function update(
        Request $request,
        #[LockForUpdate] Product $product
    ): Response {
        try {
            $this->mockableService->handle($product, $request->toArray());
        } catch (Throwable) {
            return response()->json([
                'message' => 'Unable to update the product.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return JsonResource::make($product)->response();
    }

    #[Transactional]
    public function destroy(
        #[LockForUpdate] Product $product
    ): Response {
        try {
            $this->mockableService->handle($product);
        } catch (Throwable) {
            return response()->json([
                'message' => 'Unable to delete the product.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->noContent();
    }
}
