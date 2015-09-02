<?php

namespace League\Container;

use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\Exception\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class ReflectionContainer implements
    ArgumentResolverInterface,
    ImmutableContainerInterface
{
    use ArgumentResolverTrait;
    use ImmutableContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function get($alias, array $args = [])
    {
        if (! $this->has($alias)) {
            throw new NotFoundException(
                sprintf('Alias (%s) is not an existing class and therefore cannot be resolved', $alias)
            );
        }

        $reflector = new ReflectionClass($alias);
        $construct = $reflector->getConstructor();

        if ($construct === null) {
            return new $alias;
        }

        return $reflector->newInstanceArgs(
            $this->reflectArguments($construct, $args)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function has($alias)
    {
        return class_exists($alias);
    }

    /**
     * Invoke a callable via the container.
     *
     * @param  callable $callable
     * @param  array    $args
     * @return mixed
     */
    public function call(callable $callable, array $args = [])
    {
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);

            if ($reflection->isStatic()) {
                $callable[0] = null;
            }

            return $reflection->invokeArgs($callable[0], $this->reflectArguments($reflection, $args));
        }

        $reflection = new ReflectionFunction($callable);

        return $reflection->invokeArgs($this->reflectArguments($reflection, $args));
    }
}
