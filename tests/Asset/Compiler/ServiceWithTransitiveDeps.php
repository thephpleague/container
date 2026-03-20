<?php

declare(strict_types=1);

namespace League\Container\Test\Asset\Compiler;

use League\Container\Test\Asset\FooWithRequiredDependency;

class ServiceWithTransitiveDeps
{
    public function __construct(public readonly FooWithRequiredDependency $dependency) {}
}
