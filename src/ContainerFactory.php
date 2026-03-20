<?php

declare(strict_types=1);

namespace League\Container;

use League\Container\Exception\ContainerException;
use Psr\Container\ContainerInterface;

final class ContainerFactory
{
    public static function create(
        string $compiledClass,
        string|callable $bootstrap,
        bool $useCompiled = true,
    ): ContainerInterface {
        if ($useCompiled && class_exists($compiledClass)) {
            return self::instantiateCompiledContainer($compiledClass);
        }

        return self::resolveFromBootstrap($bootstrap);
    }

    private static function instantiateCompiledContainer(string $compiledClass): ContainerInterface
    {
        $instance = new $compiledClass();

        if (!$instance instanceof ContainerInterface) {
            throw new ContainerException(sprintf(
                'Compiled class "%s" must implement %s.',
                $compiledClass,
                ContainerInterface::class,
            ));
        }

        return $instance;
    }

    private static function resolveFromBootstrap(string|callable $bootstrap): ContainerInterface
    {
        $result = is_callable($bootstrap)
            ? $bootstrap()
            : self::requireBootstrapFile($bootstrap);

        if (!$result instanceof ContainerInterface) {
            throw new ContainerException(sprintf(
                'Bootstrap must return an instance of %s, got %s.',
                ContainerInterface::class,
                get_debug_type($result),
            ));
        }

        return $result;
    }

    private static function requireBootstrapFile(string $filePath): mixed
    {
        if (!file_exists($filePath)) {
            throw new ContainerException(sprintf(
                'Bootstrap file "%s" does not exist.',
                $filePath,
            ));
        }

        return require $filePath;
    }
}
