<?php declare(strict_types=1);

namespace League\Container;

use League\Container\Argument\{ArgumentResolverInterface, ArgumentResolverTrait};
use League\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class ReflectionContainer implements ArgumentResolverInterface, ContainerInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function get($id, array $args = [])
    {
        if (! $this->has($id)) {
            throw new NotFoundException(
                sprintf('Alias (%s) is not an existing class and therefore cannot be resolved', $id)
            );
        }

        $reflector = new ReflectionClass($id);
        $construct = $reflector->getConstructor();

        if ($construct === null) {
            return new $id;
        }

        return $reflector->newInstanceArgs(
            $this->reflectArguments($construct, $args)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function has($id): bool
    {
        return class_exists($id);
    }

    /**
     * Invoke a callable via the container.
     *
     * @param callable $callable
     * @param array    $args
     *
     * @return mixed
     */
    public function call(callable $callable, array $args = [])
    {
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable)) {
            if (is_string($callable[0])) {
                $callable[0] = $this->getContainer()->get($callable[0]);
            }

            $reflection = new ReflectionMethod($callable[0], $callable[1]);

            if ($reflection->isStatic()) {
                $callable[0] = null;
            }

            return $reflection->invokeArgs($callable[0], $this->reflectArguments($reflection, $args));
        }

        if (is_object($callable)) {
            $reflection = new ReflectionMethod($callable, '__invoke');

            return $reflection->invokeArgs($callable, $this->reflectArguments($reflection, $args));
        }

        $reflection = new ReflectionFunction($callable);

        return $reflection->invokeArgs($this->reflectArguments($reflection, $args));
    }
}
