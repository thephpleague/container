<?php

declare(strict_types=1);

namespace League\Container\Test\Asset\Compiler;

use League\Container\Test\Asset\Bar;

class BarFactory
{
    public static function create(): Bar
    {
        return new Bar();
    }
}
