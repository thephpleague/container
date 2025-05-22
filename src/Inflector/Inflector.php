<?php

declare(strict_types=1);

namespace League\Container\Inflector;

use League\Container\Argument\{ArgumentResolverInterface, ArgumentResolverTrait};
use League\Container\ContainerAwareTrait;
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use ReflectionException;

class Inflector implements ArgumentResolverInterface, InflectorInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    /**
     * @var callable|null
     */
    protected $callback;

    protected array $inflected = [];

    public function __construct(
        protected string $type,
        ?callable $callback = null,
        protected bool $oncePerMatch = false,
        protected array $methods = [],
        protected array $properties = [],
    ) {
        $this->callback = $callback;
    }

    public function oncePerMatch(): InflectorInterface
    {
        $this->oncePerMatch = true;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function invokeMethod(string $name, array $args): InflectorInterface
    {
        $this->methods[$name] = $args;
        return $this;
    }

    public function invokeMethods(array $methods): InflectorInterface
    {
        foreach ($methods as $name => $args) {
            $this->invokeMethod($name, $args);
        }

        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function setProperty(string $property, mixed $value): InflectorInterface
    {
        $this->properties[$property] = $this->resolveArguments([$value])[0];
        return $this;
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setProperties(array $properties): InflectorInterface
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        return $this;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function inflect(object $object): void
    {
        if (true === $this->oncePerMatch && in_array($object, $this->inflected, true)) {
            return;
        }

        $properties = $this->resolveArguments(array_values($this->properties));
        $properties = array_combine(array_keys($this->properties), $properties);

        // array_combine() can technically return false
        foreach ($properties ?: [] as $property => $value) {
            $object->{$property} = $value;
        }

        foreach ($this->methods as $method => $args) {
            $args = $this->resolveArguments($args);
            $callable = [$object, $method];
            call_user_func_array($callable, $args);
        }

        if ($this->callback !== null) {
            call_user_func($this->callback, $object);
        }

        if (true === $this->oncePerMatch) {
            $this->inflected[] = $object;
        }
    }
}
