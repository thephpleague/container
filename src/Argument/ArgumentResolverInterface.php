<?php

declare(strict_types=1);

namespace League\Container\Argument;

use League\Container\ContainerAwareInterface;

interface ArgumentResolverInterface extends ContainerAwareInterface
{
    public function resolveArguments(array $arguments): array;
}
