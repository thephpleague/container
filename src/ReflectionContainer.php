<?php

declare(strict_types=1);

namespace League\Container;

use League\Container\Argument\{ArgumentReflectorInterface,
    ArgumentReflectorTrait,
    ArgumentResolverInterface,
    ArgumentResolverTrait};
use League\Container\Exception\NotFoundException;
use Psr\Container\{ContainerExceptionInterface, ContainerInterface, NotFoundExceptionInterface};
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class ReflectionContainer implements ArgumentReflectorInterface, ArgumentResolverInterface, ContainerInterface
{
    use ArgumentReflectorTrait;
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    public const AUTO_WIRING = 0x01;
    public const ATTRIBUTE_RESOLUTION = 0x02;

    protected array $cache = [];

    public function __construct(
        protected bool $cacheResolutions = false,
        protected int $mode = self::AUTO_WIRING | self::ATTRIBUTE_RESOLUTION
    ) {
    }

    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function get(string $id, array $args = [])
    {
        if (true === $this->cacheResolutions && array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        if (!$this->has($id)) {
            throw new NotFoundException(
                sprintf('Alias (%s) is not an existing class and therefore cannot be resolved', $id)
            );
        }

        $reflector = new ReflectionClass($id);
        $construct = $reflector->getConstructor();

        if ($construct && !$construct->isPublic()) {
            throw new NotFoundException(
                sprintf('Alias (%s) has a non-public constructor and therefore cannot be instantiated', $id)
            );
        }

        $resolution = $construct === null
            ? new $id()
            : $reflector->newInstanceArgs($this->reflectArguments($construct, $args))
        ;

        if ($this->cacheResolutions === true) {
            $this->cache[$id] = $resolution;
        }

        return $resolution;
    }

    public function has(string $id): bool
    {
        return class_exists($id);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function call(callable $callable, array $args = []): mixed
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable)) {
            if (is_string($callable[0])) {
                // if we have a definition container, try that first, otherwise, reflect
                try {
                    $callable[0] = $this->getContainer()->get($callable[0]);
                } catch (ContainerExceptionInterface | NotFoundExceptionInterface) {
                    $callable[0] = $this->get($callable[0]);
                }
            }

            $reflection = new ReflectionMethod($callable[0], $callable[1]);

            if ($reflection->isStatic()) {
                $callable[0] = null;
            }

            return $reflection->invokeArgs($callable[0], $this->reflectArguments($reflection, $args));
        }

        if (is_object($callable) && method_exists($callable, '__invoke')) {
            /** @var object $callable */
            $reflection = new ReflectionMethod($callable, '__invoke');
            return $reflection->invokeArgs($callable, $this->reflectArguments($reflection, $args));
        }

        $reflection = new ReflectionFunction($callable(...));
        return $reflection->invokeArgs($this->reflectArguments($reflection, $args));
    }
}
