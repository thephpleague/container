<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;

$container = new Container();
$container->add(Bar::class);
$container->add(Foo::class);

return $container;
