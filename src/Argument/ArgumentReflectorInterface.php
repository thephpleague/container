<?php

declare(strict_types=1);

namespace League\Container\Argument;

use League\Container\ContainerAwareInterface;
use ReflectionFunctionAbstract;

interface ArgumentReflectorInterface extends ContainerAwareInterface
{
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []): array;
}
