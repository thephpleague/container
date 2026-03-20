<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class FooWithIntersectionType
{
    public function __construct(public readonly BarInterface&Bar $dep) {}
}
