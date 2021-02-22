---
layout: post
title: Definitions
sections:
    Introduction: introduction
    Usage: usage
    Aggregate: aggregate
    Features: features
---
## Introduction

Definitions are how Container describes your dependency map internally. Each definition contains information on how to build your classes.

## Usage

Generally, Container will handle everything that is required to build a definition for you. When you invoke `add`, a `Definition` is built and returned meaning any further interaction is actually with the `Definition`, not `Container`.

~~~ php
<?php 

declare(strict_types=1);

$container  = new League\Container\Container();
$definition = $container->add(Acme\Foo::class);

var_dump($definition instanceof League\Container\Definition\Definition); // true
~~~

You can also extend a definition if needed.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container->add(Acme\Foo::class);

// ... some more code

$container->extend(Acme\Foo::class)->addArgument(Acme\Bar::class);
$container->add(Acme\Bar::class);

$foo = $container->get(Acme\Foo::class);

var_dump($foo instanceof Acme\Foo);      // true
var_dump($foo->bar instanceof Acme\Bar); // true
~~~

Creating definitions manually and passing them to the container is also possible.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$fooDefinition = (new Definition(Acme\Foo::class))->addArgument(Acme\Bar::class);
$barDefinition = (new Definition(Acme\Bar::class));

$container->add($fooDefinition->getAlias(), $fooDefinition);
$container->add($barDefinition->getAlias(), $barDefinition);

$foo = $container->get(Acme\Foo::class);

var_dump($foo instanceof Acme\Foo);      // true
var_dump($foo->bar instanceof Acme\Bar); // true
~~~

## Aggregate

Container uses an aggregate to store all definitions. This means that you can build your aggregate first and pass it off to the container.

~~~ php
<?php 

declare(strict_types=1);

$definitions = [
    (new Definition(Acme\Foo::class))->addArgument(Acme\Bar::class),
    (new Definition(Acme\Bar::class)),
];

$aggregate = new League\Container\Definition\DefinitionAggregate($definitions);
$container = new League\Container\Container($aggregate);

$foo = $container->get(Acme\Foo::class);

var_dump($foo instanceof Acme\Foo);      // true
var_dump($foo->bar instanceof Acme\Bar); // true
~~~

Interfaces are provided for definitions, and the aggregates meaning that you can build your own implementations and pass them to Container as above if you need more or less functionality defined for your dependency map.

## Features

Definitions provide several methods to define what behaviour is desired on resolution of the object you are defining. All of these methods can be chained.

### Adding Constructor Arguments

Adding an argument to a definition will pass that argument to the constructor of the defined class on instantiation, invoking this multiple times will pass more arguments, in the order they were added.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container
    ->add(Acme\Foo::class)
    ->addArgument(Acme\Bar::class)
    ->addArgument(Acme\Baz::class)
;
~~~

We also have a proxy method to pass multiple arguments in one call.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container->add(Acme\Foo::class)->addArguments([
    Acme\Bar::class,
    Acme\Baz::class,
]);
~~~

### Adding Method Calls

We can define one or multiple method calls and the arguments to be passed to them, these arguments will be resolved via the container. (The same method call can be added multiple times for multiple invokations with the same or different arguments).

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container
    ->add(Acme\Foo::class)
    ->addMethodCall('setBar', [Acme\Bar::class])
    ->addMethodCall('setBaz', [Acme\Baz::class])
;
~~~

We also have a convenience method here to add multiple method calls to the definition at once.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container
    ->add(Acme\Foo::class)
    ->addMethodCalls([
        ['setBar', [Acme\Bar::class]],
        ['setBaz', [Acme\Baz::class]],
    ])
;
~~~

### Defining Shared Objects

We can tell a definition to only resolve once and return the same instance every time it is resolved.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container
    ->add(Acme\Foo::class)
    ->setShared()
;
~~~

We also have a shortcut method to do this with one method call.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container->share(Acme\Foo::class);
~~~

If you would like to make all your definitions to default to shared, you can define that on Container, meaning that the `add` method will default to setting your definitions as shared and multiple calls to `get` will return the same instance. Only definitions after this is set will default to shared.

~~~ php
<?php 

declare(strict_types=1);

$container = (new League\Container\Container())->defaultToShared();

$container->add(Acme\Foo::class);
~~~

When the container is set to default all definitions as shared, we can specifically define a definition as not shared.

~~~ php
<?php 

declare(strict_types=1);

$container = (new League\Container\Container())->defaultToShared();

$container->add(Acme\Foo::class, Acme\Foo::class)->setShared(false);
~~~

If we have a definition marked as shared, and we want to force the retrieval of a new instance, we can invoke `getNew` instead.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container
    ->add(Acme\Foo::class)
    ->setShared()
;

$container->getNew(Acme\Foo::class);
~~~

### Tagged Definitions

We can tag definitions and retrieving the alias given to the tag will resolve all definitions using that tag in an indexed array. You can add multiple tags to each definition.

~~~ php
<?php declare(strict_types=1);

$container = new League\Container\Container();

$container->add(Acme\Foo::class)->addTag('foos');
$container->add(Acme\FooBar::class)->addTag('foos')->addTag('bars');
$container->add(Acme\Bar::class)->addTag('bars');

$foos = $container->get('foos'); // [Foo, FooBar]
$bars = $container->get('bars'); // [FooBar, Bar]
~~~
