---
layout: post
title: Auto Wiring
sections:
    Introduction: introduction
    Usage: usage
---
## Introduction

> Note: Auto wiring is turned off by default but can be turned on by registering the `ReflectionContainer` as a container delegate. Read below and see the [documentation on delegate containers](/3.x/delegate-containers/).

Container has the power to automatically resolve your objects and all of their dependencies recursively by inspecting the type hints of your constructor arguments. Unfortunately, this method of resolution has a few small limitations but is great for smaller apps. First of all, you are limited to constructor injection and secondly, all injections must be objects.

## Usage

Consider the code below.

~~~ php
<?php 

declare(strict_types=1);

namespace Acme;

class Foo
{
    /**
     * @var \Acme\Bar
     */
    public $bar;

    /**
     * @var \Acme\Baz
     */
    public $baz;

    /**
     * Construct.
     *
     * @param \Acme\Bar $bar
     * @param \Acme\Baz $baz
     */
    public function __construct(Bar $bar, Baz $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }
}

class Bar
{
    /**
     * @var \Acme\Bam
     */
    public $bam;

    /**
     * Construct.
     *
     * @param \Acme\Bam $bam
     */
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

`Acme\Foo` has 2 dependencies `Acme\Bar` and `Acme\Baz`, `Acme\Bar` has a further dependency of `Acme\Bam`. Normally you would have to do the following to return a fully configured instance of `Acme\Foo`.

~~~ php
<?php 

declare(strict_types=1);

$bam = new Acme\Bam();
$baz = new Acme\Baz();
$bar = new Acme\Bar($bam);
$foo = new Acme\Foo($bar, $baz);
~~~

With nested dependencies, this can become quite cumbersome and hard to keep track of. With the container, to return a fully configured instance of `Acme\Foo` it is as simple as requesting `Acme\Foo` from the container.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

// register the reflection container as a delegate to enable auto wiring
$container->delegate(
    new League\Container\ReflectionContainer()
);

$foo = $container->get(Acme\Foo::class);

var_dump($foo instanceof Acme\Foo);           // true
var_dump($foo->bar instanceof Acme\Bar);      // true
var_dump($foo->baz instanceof Acme\Baz);      // true
var_dump($foo->bar->bam instanceof Acme\Bam); // true
~~~

**Note:** The reflection container, by default, will resolve what you are requesting every time you request it.

If you would like the reflection container to cache resolutions and pull from that cache if available, you can enable it to do so as below.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

// register the reflection container as a delegate to enable auto wiring
$container->delegate(
    new League\Container\ReflectionContainer(true)
);

$fooOne = $container->get(Acme\Foo::class);
$fooTwo = $container->get(Acme\Foo::class);

var_dump($fooOne === $fooTwo); // true
~~~
