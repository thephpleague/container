<?php

declare(strict_types=1);

namespace League\Container\Argument;

use League\Container\DefinitionContainerInterface;
use League\Container\Exception\{ContainerException, NotFoundException};
use League\Container\ReflectionContainer;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;

trait ArgumentResolverTrait
{
    /**
     * {@inheritdoc}
     */
    public function resolveArguments(array $arguments): array
    {
        try {
            $container = $this->getContainer();
        } catch (ContainerException $e) {
            $container = ($this instanceof ReflectionContainer) ? $this : null;
        }

        foreach ($arguments as &$arg) {
            if ($arg instanceof ArgumentInterface) {
                $arg = $arg->getValue();
                continue;
            }

            if (!is_string($arg)) {
                 continue;
            }

            if ($container instanceof ContainerInterface && $container->has($arg)) {
                $arg = $container->get($arg);

                if ($arg instanceof ArgumentInterface) {
                    $arg = $arg->getValue();
                }
            }
        }

        return $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []): array
    {
        $arguments = array_map(function (ReflectionParameter $param) use ($method, $args) {
            $name = $param->getName();
            $type = $param->getType();

            if (array_key_exists($name, $args)) {
                return $args[$name];
            }

            if ($type instanceof ReflectionNamedType) {
                // in PHP 8, nullable argument have "?" prefix
                return ltrim($type->getName(), '?');
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
     * Get the container.
     *
     * @return DefinitionContainerInterface
     */
    abstract public function getContainer(): DefinitionContainerInterface;
}
