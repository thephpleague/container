<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

function test(Bar $bar): Foo
{
    return new Foo($bar);
}
