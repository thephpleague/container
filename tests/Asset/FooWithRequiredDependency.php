<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class FooWithRequiredDependency
{
    public function __construct(public readonly Bar $bar)
    {
    }
}
