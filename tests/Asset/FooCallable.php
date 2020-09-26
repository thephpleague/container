<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class FooCallable
{
    public function __invoke(Bar $bar)
    {
        return new Foo($bar);
    }
}
