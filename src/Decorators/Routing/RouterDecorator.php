<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Decorators\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Reflector;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\LockForUpdate;
use Meius\LaravelTransactionOrchestrator\Attributes\Locks\SharedLock;
use Meius\LaravelTransactionOrchestrator\Contracts\Attributes\Locks\LockAttributeContract;
use Meius\LaravelTransactionOrchestrator\Models\Scopes\LockForUpdateScope;
use Meius\LaravelTransactionOrchestrator\Models\Scopes\SharedLockScope;
use Meius\LaravelTransactionOrchestrator\Support\Reflection\ParameterAttributeInspector;
use Meius\LaravelTransactionOrchestrator\Support\Routing\ImplicitRouteBinding;
use ReflectionAttribute;
use ReflectionParameter;

class RouterDecorator extends Router
{
    protected Router $routeRegistrar;

    public function __construct(
        Dispatcher $events,
        Router $router,
        ?Container $container = null,
    ) {
        parent::__construct($events, $container);

        $this->routeRegistrar = $router;
    }

    /**
     * @param  Route  $route
     *
     * @throws ModelNotFoundException<Model>
     * @throws BackedEnumCaseNotFoundException
     */
    public function substituteBindings($route): Route
    {
        $parameters = $route->parameters();

        /** @var ReflectionParameter $signatureParameter */
        foreach ($route->signatureParameters(['subClass' => UrlRoutable::class]) as $signatureParameter) {
            $key = $signatureParameter->getName();
            $value = $parameters[$key];

            if (!isset($this->routeRegistrar->binders[$key])) {
                continue;
            }

            $reflector = ParameterAttributeInspector::from($signatureParameter);
            if ($reflector->doesntHave(LockAttributeContract::class, ReflectionAttribute::IS_INSTANCEOF)) {
                continue;
            }

            /** @var class-string<Model> $class */
            $class = Reflector::getParameterClassName($signatureParameter);

            if ($reflector->has(LockForUpdate::class)) {
                $class::addGlobalScope(LockForUpdateScope::class, new LockForUpdateScope);
            } elseif ($reflector->has(SharedLock::class)) {
                $class::addGlobalScope(SharedLockScope::class, new SharedLockScope);
            }

            $route->setParameter($key, $this->routeRegistrar->performBinding($key, $value, $route));
        }

        return $route;
    }

    /**
     * @param Route $route
     *
     * @throws ModelNotFoundException<Model>
     * @throws BackedEnumCaseNotFoundException
     */
    public function substituteImplicitBindings($route): void
    {
        $default = fn() => ImplicitRouteBinding::resolveForRoute($this->routeRegistrar->container, $route);
        $closure = $this->routeRegistrar->implicitBindingCallback ?? $default;

        call_user_func($closure, $this->routeRegistrar->container, $route, $default);
    }
}
