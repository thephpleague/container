<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class FooWithUnionType
{
    public function __construct(public readonly Bar|Baz $dep) {}
}
