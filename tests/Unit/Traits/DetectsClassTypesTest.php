<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\SharedLock;
use Meius\LaravelTransactionOrchestrator\Contracts\Attributes\Locks\LockAttributeContract;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Http\Controllers\ProductController;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Models\Product;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;
use Meius\LaravelTransactionOrchestrator\Traits\DetectsClassTypes;
use PHPUnit\Framework\Attributes\DataProvider;

class DetectsClassTypesTest  extends TestCase
{
    use DetectsClassTypes;

    #[DataProvider('positiveCases')]
    public function testReturnsTrue(object $current, array $classes): void
    {
        $this->assertTrue(
            $this->isInstanceOfAny($current, $classes)
        );
    }

    #[DataProvider('negativeCases')]
    public function testReturnsFalse(object $current, array $classes): void
    {
        $this->assertFalse(
            $this->isInstanceOfAny($current, $classes)
        );
    }

    public static function positiveCases(): array
    {
        return [
            'exact class' => [new SharedLock(), [SharedLock::class]],
            'interface' => [new SharedLock(), [LockAttributeContract::class]],
            'second in list' => [new Product(), [ProductController::class, Model::class]],
        ];
    }

    public static function negativeCases(): array
    {
        return [
            'no match' => [new Product(), [ProductController::class, SharedLock::class]],
            'empty array' => [new Product(), []],
        ];
    }
}
