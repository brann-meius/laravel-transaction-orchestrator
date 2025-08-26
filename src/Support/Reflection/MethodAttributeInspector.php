<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Support\Reflection;

use ReflectionMethod;

class MethodAttributeInspector extends AttributeInspector
{
    public static function for(?string $controller = null, ?string $method  = null): static
    {
        if (is_null($controller) || !class_exists($controller)) {
            return new static(null);
        }

        if (is_null($method) || !method_exists($controller, $method)) {
            return new static(null);
        }

        return new static(
            new ReflectionMethod($controller, $method)
        );
    }
}
