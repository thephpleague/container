<?php declare(strict_types=1);

namespace League\Container\Argument;

use League\Container\Container;
use League\Container\Exception\{ContainerException, NotFoundException};
use League\Container\ReflectionContainer;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionParameter;

trait ArgumentResolverTrait
{
    /**
     * {@inheritdoc}
     */
    public function resolveArguments(array $arguments) : array
    {
        return array_map(function ($argument) {
            if ($argument instanceof RawArgumentInterface) {
                return $argument->getValue();
            } elseif ($argument instanceof ClassNameInterface) {
                $id = $argument->getClassName();
            } elseif (!is_string($argument)) {
                return $argument;
            } else {
                $id = $argument;
            }

            $container = null;

            try {
                $container = $this->getLeagueContainer();
            } catch (ContainerException $e) {
                if ($this instanceof ReflectionContainer) {
                    $container = $this;
                }
            }

            if ($container !== null && $container->has($id)) {
                return $container->get($id);
            }

            if ($argument instanceof ClassNameWithOptionalValue) {
                return $argument->getOptionalValue();
            }

            // Just a string value.
            return $argument;
        }, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []) : array
    {
        $arguments = array_map(function (ReflectionParameter $param) use ($method, $args) {
            $name  = $param->getName();
            $type  = $param->getType();
            $class = $param->getClass();

            if (array_key_exists($name, $args)) {
                return new RawArgument($args[$name]);
            }

            if ($class) {
                if ($param->isDefaultValueAvailable()) {
                    return new ClassNameWithOptionalValue($class->getName(), $param->getDefaultValue());
                }

                return new ClassName($class->getName());
            }

            if ($type && !$param->isDefaultValueAvailable()) {
                if (PHP_VERSION_ID >= 70400) {
                    $typeName = $type->getName();
                } else {
                    $typeName = (string) $type;
                }

                // in PHP 8 nullable argument have "?" prefix
                return ltrim($typeName, '?');
            }

            if ($param->isDefaultValueAvailable()) {
                return new RawArgument($param->getDefaultValue());
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
    abstract public function getContainer() : ContainerInterface;

    /**
     * @return Container
     */
    abstract public function getLeagueContainer() : Container;
}
