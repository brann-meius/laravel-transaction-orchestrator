<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Support\Reflection;

use ReflectionAttribute;
use ReflectionMethod;
use ReflectionParameter;

abstract class AttributeInspector
{
    protected function __construct(
        protected null|ReflectionMethod|ReflectionParameter $reflector = null,
    ) {
        //
    }

    public static function from(ReflectionMethod|ReflectionParameter $reflector): static
    {
        return new (static::class)($reflector);
    }

    /**
     * Checks if the reflection has the given attribute.
     *
     * @param class-string $attribute
     */
    public function has(string $attribute, int $flags = 0): bool
    {
        if ($this->reflector === null) {
            return false;
        }

        return $this->get($attribute, $flags) !== [];
    }

    /**
     * Determine if the reflection target does not have the given attribute.
     *
     * @param class-string $attribute
     */
    public function doesntHave(string $attribute, int $flags = 0): bool
    {
        return !$this->has($attribute, $flags);
    }

    /**
     * Returns all attributes of the given type for the reflection target.
     *
     * @param class-string $attribute
     *
     * @return ReflectionAttribute[]
     */
    public function get(string $attribute, int $flags = 0): array
    {
        if ($this->reflector === null) {
            return [];
        }

        return $this->reflector->getAttributes($attribute, $flags);
    }

    public function first(string $attribute, int $flags = 0): false|ReflectionAttribute
    {
        return current($this->get($attribute, $flags));
    }
}
