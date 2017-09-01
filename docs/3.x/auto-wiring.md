---
layout: default
title: Auto Wiring
---

# Auto Wiring

> Note: Auto wiring is turned off by default but can be turned on by registering the `ReflectionContainer` as a container delegate. Read below and see the [documentation on delegates](/delegate-containers/).

Container has the power to automatically resolve your objects and all of their dependencies recursively by inspecting the type hints of your constructor arguments. Unfortunately, this method of resolution has a few small limitations but is great for smaller apps. First of all, you are limited to constructor injection and secondly, all injections must be objects.

~~~ php
<?php

namespace Acme;

class Foo
{
    public $bar;

    public $baz;

    public function __construct(Bar $bar, Baz $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }
}
~~~

~~~ php
<?php

namespace Acme;

class Bar
{
    public $bam;

    public function __construct(Bam $bam)
    {
        $this->bam = $bam;
    }
}
~~~

~~~ php
<?php

namespace Acme;

class Baz
{
    // ..
}
~~~

~~~ php
<?php

namespace Acme;

class Bam
{
    // ..
}
~~~

In the above code, `Foo` has 2 dependencies `Bar` and `Baz`, `Bar` has a further dependency of `Bam`. Normally you would have to do the following to return a fully configured instance of `Foo`.

~~~ php
<?php

$bam = new Acme\Bam;
$baz = new Acme\Baz;
$bar = new Acme\Bar($bam);
$foo = new Acme\Foo($bar, $baz);
~~~

With nested dependencies, this can become quite cumbersome and hard to keep track of. With the container, to return a fully configured instance of `Foo` it is as simple as requesting `Foo` from the container.

~~~ php
<?php

$container = new League\Container\Container;

// register the reflection container as a delegate to enable auto wiring
$container->delegate(
    new League\Container\ReflectionContainer
);

$foo = $container->get('Acme\Foo');

var_dump($foo instanceof Acme\Foo); // true
var_dump($foo->bar instanceof Acme\Bar); // true
var_dump($foo->baz instanceof Acme\Baz); // true
var_dump($foo->bar->bam instanceof Acme\Bam); // true
~~~
