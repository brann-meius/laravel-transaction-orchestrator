<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Unit\Support\Reflection;

use Meius\LaravelTransactionOrchestrator\Attributes\Transactional;
use Meius\LaravelTransactionOrchestrator\Support\Reflection\MethodAttributeInspector;
use Meius\LaravelTransactionOrchestrator\Tests\Support\Http\Controllers\ProductController;
use Meius\LaravelTransactionOrchestrator\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionAttribute;

class MethodAttributeInspectorTest extends TestCase
{
    #[Test, DataProvider('invalidForProvider')]
    public function forWithInvalidInputsBehavesAsEmpty(?string $controller, ?string $method): void
    {
        $inspector = MethodAttributeInspector::for($controller, $method);

        $this->assertFalse($inspector->has(Transactional::class));
        $this->assertTrue($inspector->doesntHave(Transactional::class));
        $this->assertSame([], $inspector->get(Transactional::class));
        $this->assertFalse($inspector->first(Transactional::class));
    }

    #[Test, DataProvider('validForProvider')]
    public function forWithValidControllerAndMethod(
        string $controller,
        string $method,
        string $attribute,
        bool $expectedHas,
        int $expectedCount,
    ): void {
        $inspector = MethodAttributeInspector::for($controller, $method);

        $this->assertSame($expectedHas, $inspector->has($attribute));
        $this->assertSame(!$expectedHas, $inspector->doesntHave($attribute));

        $this->assertCount($expectedCount, $inspector->get($attribute));

        $first = $inspector->first($attribute);
        if ($expectedCount === 0) {
            $this->assertFalse($first);
            return;
        }

        /** @var Transactional $instance */
        $instance = $first->newInstance();

        $this->assertInstanceOf(ReflectionAttribute::class, $first);
        $this->assertObjectHasProperty('connections', $instance);
        $this->assertSame([$this->connection], $instance->connections);
    }

    public static function invalidForProvider(): array
    {
        return [
            'null controller' => [null, 'index'],
            'unknown controller' => ['\\Not\\Existing\\Controller', 'index'],
            'existing controller, null method' => [ProductController::class, null],
            'existing controller, unknown method' => [ProductController::class, 'nope'],
        ];
    }

    public static function validForProvider(): array
    {
        return [
            'method with one attribute' => [
                ProductController::class,
                'update',
                Transactional::class,
                true,
                1,
            ],
            'method without attribute'  => [
                ProductController::class,
                'index',
                Transactional::class,
                false,
                0,
            ],
        ];
    }
}
