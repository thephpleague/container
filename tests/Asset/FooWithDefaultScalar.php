<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class FooWithDefaultScalar
{
    public function __construct(public readonly string $name = 'default') {}
}
