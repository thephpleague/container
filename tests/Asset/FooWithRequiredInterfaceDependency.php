<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class FooWithRequiredInterfaceDependency
{
    public function __construct(public readonly BarInterface $bar) {}
}
