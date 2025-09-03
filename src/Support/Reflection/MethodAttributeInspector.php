<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Support\Reflection;

use ReflectionMethod;

class MethodAttributeInspector extends AttributeInspector
{
    public static function for(?string $controller = null, ?string $method  = null): static
    {
        if (is_null($controller) || !class_exists($controller)) {
            return new (static::class)(null);
        }

        if (is_null($method) || !method_exists($controller, $method)) {
            return new (static::class)(null);
        }

        return new (static::class)(
            new ReflectionMethod($controller, $method)
        );
    }
}
