<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Support\Reflection;

use Meius\LaravelTransactionOrchestrator\Attributes\Locks\LockForUpdate;
use Meius\LaravelTransactionOrchestrator\Contracts\Attributes\Locks\LockAttributeContract;
use Meius\LaravelTransactionOrchestrator\Support\Reflection\ParameterAttributeInspector;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Http\Controllers\ProductController;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionAttribute;
use ReflectionMethod;

class ParameterAttributeInspectorTest extends TestCase
{
    #[Test, DataProvider('validParamsProvider')]
    public function fromWithReflectionParameter(
        string $controller,
        string $method,
        int $index,
        string $attribute,
        bool $expectedHas,
    ): void {
        $method = new ReflectionMethod($controller, $method);
        $param = $method->getParameters()[$index];

        $inspector = ParameterAttributeInspector::from($param);

        $this->assertSame($expectedHas, $inspector->has($attribute));
        $this->assertSame(!$expectedHas, $inspector->doesntHave($attribute));

        if ($expectedHas) {
            $this->assertInstanceOf(ReflectionAttribute::class, $inspector->first($attribute));
        } else {
            $this->assertFalse($inspector->first($attribute));
        }
    }

    #[Test, DataProvider('instanceofProvider')]
    public function getWithIsInstanceofFlag(
        string $controller,
        string $method,
        int $index,
        int $expectedCount
    ): void {
        $method = new ReflectionMethod($controller, $method);
        $param = $method->getParameters()[$index];

        $inspector = ParameterAttributeInspector::from($param);
        $attrs = $inspector->get(LockAttributeContract::class, ReflectionAttribute::IS_INSTANCEOF);

        $this->assertCount($expectedCount, $attrs);
        if ($expectedCount) {
            $this->assertInstanceOf(ReflectionAttribute::class, $attrs[0]);
        }
    }

    public static function validParamsProvider(): array
    {
        return [
            'single attribute' => [
                'controller' => ProductController::class,
                'method' => 'update',
                'paramIndex' => 1,
                'attribute' => LockForUpdate::class,
                'expectedHas' => true,
            ],
            'no attribute' => [
                'controller' => ProductController::class,
                'method' => 'update',
                'paramIndex' => 0,
                'attribute' => LockForUpdate::class,
                'expectedHas' => false,
            ],
        ];
    }

    public static function instanceofProvider(): array
    {
        return [
            'child matches base with IS_INSTANCEOF' => [
                'controller' => ProductController::class,
                'method' => 'update',
                'index' => 1,
                'expectedCount' => 1,
            ],
            'not matching different attr' => [
                'controller' => ProductController::class,
                'method' => 'update',
                'index' => 0,
                'expectedCount' => 0,
            ],
        ];
    }
}
