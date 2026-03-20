<?php

declare(strict_types=1);

namespace League\Container\Definition;

use ArgumentCountError;
use League\Container\Argument\ArgumentInterface;
use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\Argument\LiteralArgumentInterface;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\ContainerException;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

class Definition implements ArgumentResolverInterface, DefinitionInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    protected mixed $resolved = null;

    /**
     * @param array<int, mixed> $arguments
     * @param list<array{method: string, arguments: array<int, mixed>}> $methods
     * @param array<string, bool> $tags
     */
    public function __construct(
        protected string $id,
        protected mixed $concrete = null,
        protected bool $shared = false,
        protected array $arguments = [],
        protected array $methods = [],
        protected array $tags = [],
    ) {
        $this->setId($this->id);
        $this->concrete ??= $this->id;
    }

    #[Override]
    public function addTag(string $tag): DefinitionInterface
    {
        $this->tags[$tag] = true;
        return $this;
    }

    /** @return list<string> */
    #[Override]
    public function getTags(): array
    {
        return array_keys($this->tags);
    }

    #[Override]
    public function hasTag(string $tag): bool
    {
        return isset($this->tags[$tag]);
    }

    public function setId(string $id): DefinitionInterface
    {
        $this->id = static::normaliseAlias($id);
        return $this;
    }

    public function getId(): string
    {
        return static::normaliseAlias($this->id);
    }

    #[Override]
    public function setAlias(string $id): DefinitionInterface
    {
        return $this->setId($id);
    }

    #[Override]
    public function getAlias(): string
    {
        return $this->getId();
    }

    #[Override]
    public function setShared(bool $shared = true): DefinitionInterface
    {
        $this->shared = $shared;
        return $this;
    }

    #[Override]
    public function isShared(): bool
    {
        return $this->shared;
    }

    #[Override]
    public function getConcrete(): mixed
    {
        return $this->concrete;
    }

    #[Override]
    public function setConcrete(mixed $concrete): DefinitionInterface
    {
        $this->concrete = $concrete;
        $this->resolved = null;
        return $this;
    }

    #[Override]
    public function addArgument(mixed $arg): DefinitionInterface
    {
        $this->arguments[] = $arg;
        return $this;
    }

    /** @param array<int, mixed> $args */
    #[Override]
    public function addArguments(array $args): DefinitionInterface
    {
        foreach ($args as $arg) {
            $this->addArgument($arg);
        }

        return $this;
    }

    /** @param array<int, mixed> $args */
    #[Override]
    public function addMethodCall(string $method, array $args = []): DefinitionInterface
    {
        $this->methods[] = [
            'method' => $method,
            'arguments' => $args,
        ];

        return $this;
    }

    /** @param array<string, array<int, mixed>> $methods */
    #[Override]
    public function addMethodCalls(array $methods = []): DefinitionInterface
    {
        foreach ($methods as $method => $args) {
            $this->addMethodCall($method, $args);
        }

        return $this;
    }

    /** @return array<int, mixed> */
    #[Override]
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /** @return list<array{method: string, arguments: array<int, mixed>}> */
    #[Override]
    public function getMethodCalls(): array
    {
        return $this->methods;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Override]
    public function resolve(): mixed
    {
        if (null !== $this->resolved && $this->isShared()) {
            return $this->resolved;
        }

        return $this->resolveNew();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Override]
    public function resolveNew(): mixed
    {
        $concrete = $this->concrete;

        try {
            $container = $this->getContainer();
        } catch (ContainerException) {
            $container = null;
        }

        if (is_callable($concrete)) {
            $concrete = $this->resolveCallable($concrete);
        }

        if ($concrete instanceof LiteralArgumentInterface) {
            $this->resolved = $concrete->getValue();
            return $concrete->getValue();
        }

        if ($concrete instanceof ArgumentInterface) {
            $concrete = $concrete->getValue();
        }

        if (is_string($concrete) && $concrete !== $this->getId()) {
            if ($container instanceof ContainerInterface && $container->has($concrete)) {
                $concrete = $container->get($concrete);
            } elseif (class_exists($concrete)) {
                $concrete = $this->resolveClass($concrete);
            }
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $concrete = $this->resolveClass($concrete);
        }

        if (is_object($concrete)) {
            $concrete = $this->invokeMethods($concrete);
        }

        if (is_string($concrete)) {
            if ($concrete !== $this->getId() && $container instanceof ContainerInterface && $container->has($concrete)) {
                $concrete = $container->get($concrete);
            } elseif (class_exists($concrete)) {
                $concrete = $this->resolveClass($concrete);
            } elseif ($this->getAlias() === $concrete) {
                return $concrete;
            }
        }

        if (is_string($concrete) && $concrete !== $this->getId() && $container instanceof ContainerInterface && $container->has($concrete)) {
            $concrete = $container->get($concrete);
        }

        $this->resolved = $concrete;
        return $concrete;
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolveCallable(callable $concrete): mixed
    {
        $resolved = $this->resolveArguments($this->arguments);
        return $concrete(...$resolved);
    }

    /**
     * @param class-string $concrete
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    protected function resolveClass(string $concrete): object
    {
        $resolved   = $this->resolveArguments($this->arguments);
        $reflection = new ReflectionClass($concrete);

        try {
            return $reflection->newInstanceArgs($resolved);
        } catch (ArgumentCountError $e) {
            throw new ContainerException(sprintf(
                'Class "%s" was registered as a definition but its constructor has '
                . 'unsatisfied dependencies. Either provide arguments using '
                . '->addArgument(), use a callable to construct the class, or remove '
                . 'the explicit registration to allow autowiring via a delegate container.',
                $concrete,
            ), 0, $e);
        }
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function invokeMethods(object $instance): object
    {
        foreach ($this->methods as $method) {
            $args = $this->resolveArguments($method['arguments']);
            $instance->{$method['method']}(...$args);
        }

        return $instance;
    }

    public static function normaliseAlias(string $alias): string
    {
        return ltrim($alias, '\\');
    }
}
