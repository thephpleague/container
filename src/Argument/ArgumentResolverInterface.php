<?php

declare(strict_types=1);

namespace League\Container\Argument;

use League\Container\ContainerAwareInterface;

interface ArgumentResolverInterface extends ContainerAwareInterface
{
    /** @param array<int, mixed> $arguments
     *  @return array<int, mixed> */
    public function resolveArguments(array $arguments): array;
}
