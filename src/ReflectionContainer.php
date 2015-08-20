<?php

namespace League\Container;

use Leagie\Container\ImmutableContainerAwareTrait;
use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\ImmutableContainerAwareInterface;
use League\Container\ImmutableContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class ReflectionContainer implements
    ArgumentResolverInterface,
    ImmutableContainerAwareInterface,
    ImmutableContainerInterface
{
    use ArgumentResolverTrait;
    use ImmutableContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function get($alias, array $args = [])
    {
        try {
            $reflector = new ReflectionClass($alias);
            $construct = $reflector->getConstructor();
        } catch (ReflectionException $e) {
            return new $alias;
        }

        return $reflector->newInstanceArgs(
            $this->reflectArguments($args)
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
            $reflection  = new ReflectionMethod($callable[0], $callable[1]);
            $callable[0] = ($this->getContainer()->has($callable[0]))
                         ? $this->getContainer()->get($callable[0])
                         : new $callable[0];

            return $reflection->invokeArgs($callable[0], $this->reflectArguments($reflection, $args));
        }

        $reflection = new ReflectionFunction($callable);

        return $reflection->invokeArgs($this->reflectArguments($reflection, $args));
    }
}
