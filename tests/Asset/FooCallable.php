<?php

namespace League\Container\Test\Asset;

class FooCallable
{
    public function __invoke(Bar $bar)
    {
        return new Foo($bar);
    }
}
