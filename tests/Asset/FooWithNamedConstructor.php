<?php

namespace League\Container\Test\Asset;

class FooWithNamedConstructor
{
    public function __invoke(Bar $bar)
    {
        return new Foo($bar);
    }

    public function namedConstructor(Bar $bar)
    {
        return new Foo($bar);
    }

    public static function staticNamedConstructor(Bar $bar)
    {
        return new Foo($bar);
    }
}
