---
layout: default
permalink: /auto-dependency-resolution/
title: Auto Dependency Resolution
---

# Auto Dependency Resolution

Container has the power to automatically resolve your objects and all of their dependencies recursively by inspecting the type hints of your constructor arguments. Unfortunately, this method of resolution has a few small limitations but is great for smaller apps. First of all, you are limited to constructor injection and secondly, all injections must be objects.

~~~ php
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

class Bar
{
    public $bam;

    public function __construct(Bam $bam)
    {
        $this->bam = $bam;
    }
}

class Baz
{
    // ..
}

class Bam
{
    // ..
}
~~~

In the above code, `Foo` has 2 dependencies `Bar` and `Baz`, `Bar` has a further dependency of `Bam`. Normally you would have to do the following to return a fully configured instance of `Foo`.

~~~ php
$bam = new Bam;
$baz = new Baz;
$bar = new Bar($bam);
$foo = new Foo($bar, $baz);
~~~

With nested dependencies, this can become quite cumbersome and hard to keep track of. With the container, to return a fully configured instance of `Foo` it is as simple as requesting `Foo` from the container.

~~~ php
$container = new League\Container\Container;

$foo = $container->get('Foo');
~~~
