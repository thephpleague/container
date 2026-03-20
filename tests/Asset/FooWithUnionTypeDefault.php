<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class FooWithUnionTypeDefault
{
    public function __construct(public readonly Bar|Baz|null $dep = null) {}
}
