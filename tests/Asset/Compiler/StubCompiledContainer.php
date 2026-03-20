<?php

declare(strict_types=1);

namespace League\Container\Test\Asset\Compiler;

use League\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;

final class StubCompiledContainer implements ContainerInterface
{
    public function get(string $id): mixed
    {
        throw new NotFoundException(sprintf('Service "%s" not found.', $id));
    }

    public function has(string $id): bool
    {
        return false;
    }
}
