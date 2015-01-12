<?php

namespace League\Container\Test\Asset;

class BazStatic
{
    public static function baz($foo)
    {
        return $foo;
    }

    public function qux()
    {
        return 'qux';
    }
}
