<?php

namespace NAL\DependenciesResolver;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

class DependenciesResolver
{
    /**
     * Class to resolve
     *
     * @var string|object
     */
    private string|object $class = '';

    /**
     * ResolveDependencies constructor.
     *
     * @param string|object|null $class The class name to resolve dependencies for.
     * @throws InvalidArgumentException If the class does not exist.
     */
    public function __construct(string|object $class = null)
    {
        $this->class = $class ?? '';
    }

    /**
     * Resolves the constructor dependencies and instantiates the class.
     *
     * @param string|object|null $class The class name or object to resolve dependencies for.
     * @return object The instantiated object with resolved dependencies.
     * @throws InvalidArgumentException If the class is not defined.
     */
    public function constructor(string|object $class = null): object
    {
        $this->class = $class ?? $this->class;

        if (empty($this->class)) {
            throw new InvalidArgumentException("Need to define class first to resolve");
        }

        return $this->resolve('constructor');
    }

    /**
     * Resolves the method dependencies and invokes the method.
     *
     * @param string $method The method name to resolve dependencies for.
     * @param string|object|null $class The class name or object to resolve dependencies for.
     * @return mixed The result of the method call with resolved dependencies.
     * @throws InvalidArgumentException If the class or method is not defined.
     */
    public function method(string $method, string|object $class = null): mixed
    {
        $this->class = $class ?? $this->class;

        if (empty($this->class)) {
            throw new InvalidArgumentException("Need to define class first to resolve");
        }

        return $this->resolve('method', $method);
    }

    /**
     * Resolves the dependencies for a callable function and invokes it.
     *
     * @param callable $callback The callable function to resolve dependencies for.
     * @return mixed The result of the callable function with resolved dependencies.
     * @throws InvalidArgumentException If the callable is not defined.
     */
    public function callback(callable $callback): mixed
    {
        return $this->resolve('callable', null, $callback);
    }

    /**
     * Resolves dependencies for the given type.
     *
     * @param string $type The type of resolution ('constructor' or 'method').
     * @param string|null $method The method name (if resolving method dependencies).
     * @param callable|null $callable The callable function (if resolving callable dependencies).
     * @return mixed The instantiated object with resolved dependencies.
     * @throws InvalidArgumentException If the type is unsupported or the class is not instantiable.
     * @throws ReflectionException If the method does not exist.
     */
    private function resolve(string $type, string $method = null, callable $callable = null): mixed
    {
        $type = strtolower($type);

        if ($type === 'constructor' || $type === 'method') {
            $reflector = new ReflectionClass($this->class);

            if (!$reflector->isInstantiable()) {
                throw new InvalidArgumentException(sprintf("%s is not instantiable", is_object($this->class) ? $this->class::class : $this->class));
            }

            if ($type === 'constructor') {

                return $this->resolveConstructor($reflector);
            } elseif ($type === 'method') {
                if ($method === null) {
                    throw new InvalidArgumentException("Method name must be provided for method resolution");
                }

                return $this->resolveMethod($reflector, $method);
            }
        } elseif ($type === 'callable') {
            if ($callable === null) {
                throw new InvalidArgumentException("Callable must be provided for callable resolution");
            }

            return $this->resolveCallable($callable);
        } else {
            throw new InvalidArgumentException("Unsupported type {$type} to resolve");
        }
    }

    /**
     * Resolves constructor dependencies and instantiates the class.
     *
     * @param ReflectionClass $reflector The reflection class instance.
     * @return object The instantiated object with resolved constructor dependencies.
     * @throws InvalidArgumentException If a dependency cannot be resolved.
     */
    private function resolveConstructor(ReflectionClass $reflector): object
    {
        $constructor = $reflector->getConstructor();

        $class = $reflector->getName();

        if ($constructor !== null) {
            $constructorParams = $constructor->getParameters();

            $constructorResolvedDependencies = [];

            foreach ($constructorParams as $param) {
                $constructorResolvedDependencies[] = $this->resolveParameter($param);
            }

            return new $class(...$constructorResolvedDependencies);
        }

        return new $class();
    }

    /**
     * Resolves method dependencies and invokes the method.
     *
     * @param ReflectionClass $reflector The reflection class instance.
     * @param string $method The method name to resolve dependencies for.
     * @return mixed The result of the method call with resolved dependencies.
     * @throws InvalidArgumentException If a dependency cannot be resolved.
     * @throws ReflectionException If the method does not exist.
     */
    private function resolveMethod(ReflectionClass $reflector, string $method): mixed
    {
        $method = $reflector->getMethod($method);

        $parameters = $method->getParameters();

        $instance = $this->resolveConstructor($reflector);

        if (!empty($parameters)) {
            $methodResolvedDependencies = [];
            foreach ($parameters as $parameter) {
                $methodResolvedDependencies[] = $this->resolveParameter($parameter);
            }

            return $instance->{$method->getName()}(...$methodResolvedDependencies);
        }

        return $instance->{$method->getName()}();
    }

    /**
     * Resolves a parameter dependency.
     *
     * @param \ReflectionParameter $param The reflection parameter instance.
     * @return mixed The resolved parameter value.
     * @throws InvalidArgumentException If a dependency cannot be resolved.
     */
    private function resolveParameter(\ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if ($type === null || $type->isBuiltin()) {
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            throw new InvalidArgumentException("Unable to resolve dependency parameter for `{$param->getName()}`");
        }

        $className = $type->getName();

        return (new self())->constructor($className);
    }

    /**
     * Resolves dependencies for a callable function.
     *
     * @param callable $callable The callable function to resolve dependencies for.
     * @return mixed The result of the callable function with resolved dependencies.
     * @throws InvalidArgumentException If a dependency cannot be resolved.
     * @throws ReflectionException If the callable is not a valid function or method.
     */
    private function resolveCallable(callable $callable): mixed
    {
        $reflector = new ReflectionFunction($callable);
        $parameters = $reflector->getParameters();

        $resolvedDependencies = [];

        foreach ($parameters as $parameter) {
            $resolvedDependencies[] = $this->resolveParameter($parameter);
        }

        return $callable(...$resolvedDependencies);
    }
}
