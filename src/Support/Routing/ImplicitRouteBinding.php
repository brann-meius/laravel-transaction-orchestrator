<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Support\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Routing\ImplicitRouteBinding as BaseImplicitRouteBinding;
use Illuminate\Support\Reflector;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\LockForUpdate;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\SharedLock;
use Meius\LaravelTransactionOrchestrator\Contracts\Attributes\Locks\LockAttributeContract;
use Meius\LaravelTransactionOrchestrator\Models\Scopes\LockForUpdateScope;
use Meius\LaravelTransactionOrchestrator\Models\Scopes\SharedLockScope;
use Meius\LaravelTransactionOrchestrator\Support\Reflection\ParameterAttributeInspector;
use ReflectionAttribute;
use ReflectionParameter;

class ImplicitRouteBinding extends BaseImplicitRouteBinding
{
    public static function resolveForRoute($container, $route): void
    {
        $parameters = $route->parameters();

        $route = static::resolveBackedEnumsForRoute($route, $parameters);

        /** @var ReflectionParameter $parameter */
        foreach ($route->signatureParameters(['subClass' => UrlRoutable::class]) as $parameter) {
            if (! $parameterName = static::getParameterName($parameter->getName(), $parameters)) {
                continue;
            }

            $parameterValue = $parameters[$parameterName];

            if ($parameterValue instanceof UrlRoutable) {
                continue;
            }

            $reflector = ParameterAttributeInspector::from($parameter);
            if ($reflector->doesntHave(LockAttributeContract::class, ReflectionAttribute::IS_INSTANCEOF)) {
                continue;
            }

            /** @var Model $instance */
            $instance = $container->make(Reflector::getParameterClassName($parameter));

            if ($reflector->has(LockForUpdate::class)) {
                $instance::addGlobalScope(LockForUpdateScope::class, new LockForUpdateScope);
            } elseif ($reflector->has(SharedLock::class)) {
                $instance::addGlobalScope(SharedLockScope::class, new SharedLockScope);
            }

            $parent = $route->parentOfParameter($parameterName);

            $routeBindingMethod = $route->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))
                ? 'resolveSoftDeletableRouteBinding'
                : 'resolveRouteBinding';

            if ($parent instanceof UrlRoutable &&
                ! $route->preventsScopedBindings() &&
                ($route->enforcesScopedBindings() || array_key_exists($parameterName, $route->bindingFields()))) {
                $childRouteBindingMethod = $route->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))
                    ? 'resolveSoftDeletableChildRouteBinding'
                    : 'resolveChildRouteBinding';

                if (! $model = $parent->{$childRouteBindingMethod}(
                    $parameterName, $parameterValue, $route->bindingFieldFor($parameterName)
                )) {
                    throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
                }
            } elseif (! $model = $instance->{$routeBindingMethod}($parameterValue, $route->bindingFieldFor($parameterName))) {
                throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
            }

            $route->setParameter($parameterName, $model);
        }
    }
}
