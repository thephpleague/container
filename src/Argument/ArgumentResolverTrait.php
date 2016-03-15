<?php

namespace League\Container\Argument;

use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use ReflectionFunctionAbstract;
use ReflectionParameter;

trait ArgumentResolverTrait
{
    /**
     * {@inheritdoc}
     */
    public function resolveArguments(array $arguments)
    {
        foreach ($arguments as &$arg) {
            if ($arg instanceof RawArgumentInterface) {
                $arg = $arg->getValue();
                continue;
            }

            if (! is_string($arg)) {
                 continue;
            }

            $container = $this->getContainer();

            if (is_null($container) && $this instanceof ReflectionContainer) {
                $container = $this;
            }

            if (! is_null($container) && $container->has($arg)) {
                $arg = $container->get($arg);

                if ($arg instanceof RawArgumentInterface) {
                    $arg = $arg->getValue();
                }

                continue;
            }
        }

        return $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = [])
    {
        $arguments = array_map(function (ReflectionParameter $param) use ($method, $args) {
            $name  = $param->getName();
            $class = $param->getClass();

            if (array_key_exists($name, $args)) {
                return $args[$name];
            }

            if (! is_null($class)) {
                return $class->getName();
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            throw new NotFoundException(sprintf(
                'Unable to resolve a value for parameter (%s) in the function/method (%s)',
                $name,
                $method->getName()
            ));
        }, $method->getParameters());

        return $this->resolveArguments($arguments);
    }

    /**
     * @return ContainerInterface
     */
    abstract public function getContainer();
}
