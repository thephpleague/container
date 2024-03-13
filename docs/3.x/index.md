---
layout: post
title: Getting Started
sections:
    Key Features: key-features
    Introduction: introduction
    Further Reading: further-reading
    Installation: installation
    Basic Usage: basic-usage
    Questions?: questions
---
[![Author](http://img.shields.io/badge/author-@philipobenito-blue.svg?style=for-the-badge)](https://twitter.com/philipobenito)
[![Latest Version](https://img.shields.io/github/v/release/thephpleague/container?label=latest&style=for-the-badge)](https://github.com/thephpleague/container/releases)

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=for-the-badge)](LICENSE.md)
[![Build Status](https://img.shields.io/github/workflow/status/thephpleague/container/Tests/3.x?style=for-the-badge)](https://github.com/thephpleague/container/actions)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/thephpleague/container.svg?style=for-the-badge)](https://scrutinizer-ci.com/g/thephpleague/container/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/container.svg?style=for-the-badge)](https://scrutinizer-ci.com/g/thephpleague/container)
[![Total Downloads](https://img.shields.io/packagist/dt/league/container.svg?style=for-the-badge)](https://packagist.org/packages/league/container)

## Key Features

1. Simple API
2. Interoperabiity. Container is an implementation of [PSR-11](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md).
3. Speed. Because Container is simple, it is also very fast.
4. Service Providers allow you to package code or configuration for packages that you reuse regularly.
5. Inflectors allow you to manipulate objects resolved through the container based on the type.

## Introduction

Container is dependency injection container. It allows you to implement the [dependency injection design pattern](https://en.wikipedia.org/wiki/Dependency_injection) meaning that you can decouple your class dependencies and have the container inject them where they are needed.

~~~ php
<?php declare(strict_types=1);

namespace Acme;

class Foo
{
    /**
     * @var \Acme\Bar
     */
    public $bar;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->bar = new Bar;
    }
}

class Bar {}
~~~

The class above `Acme\Foo` has a dependency on `Acme\Bar`, as it is written, this is a tightly coupled dependency meaning that every time `Acme\Foo` is instantiated, it takes it upon itself to also instantiate `Acme\Bar`.

By refactoring the code above to have the class accept it's dependency as a constructor argument, we can loosen that dependency.

~~~ php
<?php declare(strict_types=1);

namespace Acme;

class Foo
{
    /**
     * @var \Acme\Bar
     */
    public $bar;

    /**
     * Construct.
     *
     * @param \Acme\Bar $bar
     */
    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}

class Bar {}
~~~

Dependency injection at it's core is as simple as that, and Container allows you to implement this in your applications.

## Further Reading

I recommend reading the links below for further information about what problems dependency injection solve.

- [Dependency Injection Wikipedia Entry](https://en.wikipedia.org/wiki/Dependency_injection).
- [Inversion of Control Wikipedia Entry](https://en.wikipedia.org/wiki/Inversion_of_control).
- [Dependency Injection on PHP The Right Way](https://www.phptherightway.com/#dependency_injection) initially written by Phil Bennett.
- [Learning About Dependency Injection and PHP](http://ralphschindler.com/2011/05/18/learning-about-dependency-injection-and-php) by Ralph Schindler.
- [Dependency Injection as a Tool for Testing](https://medium.com/philipobenito/dependency-injection-as-a-tool-for-testing-902c21c147f1) by Phil Bennett.
- [The 'D' Doesn't Stand for Dependency Injection](https://www.brandonsavage.net/the-d-doesnt-stand-for-dependency-injection/) by Brandon Savage.

## Installation

### System Requirements

You need `PHP >= 7.0.0` to use `League\Container` but the latest stable version of PHP is recommended.

### Composer

Container is available on [Packagist](https://packagist.org/packages/league/container) and can be installed using [Composer](https://getcomposer.org/):

~~~
composer require league/container
~~~

Most modern frameworks will include Composer out of the box, but ensure the following file is included:

~~~ php
<?php

// include the Composer autoloader
require 'vendor/autoload.php';
~~~

### Going Solo

You can also use Container without using Composer by registering an autoloader function:

~~~ php
spl_autoload_register(function ($class) {
    $prefix = 'League\\Container\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
~~~

Or, use any other [PSR-4](http://www.php-fig.org/psr/psr-4/) compatible autoloader.

## Basic Usage

Container allows you to register services, with or without their dependencies for later retrieval. It is a registry of sorts that when used correctly can allow you to implement the dependency injection design pattern.

Using the example in our [introduction](#introduction), we can start to take a look at how Container works. Now that `Acme\Foo` accepts `Acme\Bar` as a constructor argument, we can use Container to configure that.

~~~ php
<?php declare(strict_types=1);

$container = new League\Container\Container;

$container->add(Acme\Foo::class)->addArgument(Acme\Bar::class);
$container->add(Acme\Bar::class);

$foo = $container->get(Acme\Foo::class);

var_dump($foo instanceof Acme\Foo);      // true
var_dump($foo->bar instanceof Acme\Bar); // true
~~~

In the example above, we have registered both `Acme\Foo` and `Acme\Bar` with Container, we have also told Container that when we retrieve `Acme\Foo` we want to retrieve it with an argument of `Acme\Bar`.

When we ask Container to `get(Acme\Foo::class)`, it knows to first get `Acme\Bar` and inject it as a constructor argument when instantiating `Acme\Foo`.

#### Aliases

We can make a slight adjustment to the code above so that we can use aliases to point to an actual class.

~~~ php
<?php declare(strict_types=1);

$container = new League\Container\Container;

$container->add('foo', Acme\Foo::class)->addArgument(Acme\Bar::class);
$container->add('bar', Acme\Bar::class);

$foo = $container->get('foo');

var_dump($foo instanceof Acme\Foo);      // true
var_dump($foo->bar instanceof Acme\Bar); // true
~~~

This is useful especially when depending on interfaces rather than concretions. We can refactor our original example to have it depend on an interface instead, and configure Container to inject the concrete implementation.

~~~ php
<?php declare(strict_types=1);

namespace Acme;

class Foo
{
    /**
     * @var \Acme\BarInterface
     */
    public $bar;

    /**
     * Construct.
     *
     * @param \Acme\BarInterface $bar
     */
    public function __construct(BarInterface $bar)
    {
        $this->bar = $bar;
    }
}

interface BarInterface {}
class BarA implements BarInterface {}
class BarB implements BarInterface {}
~~~

We now have `Acme\Foo` depending on an implementation of `Acme\BarInterface` (`Acme\BarA` or `Acme\BarB`).

~~~ php
<?php declare(strict_types=1);

$container = new League\Container\Container;

// Acme\Foo is added as normal but with an argument of Acme\BarInterface
$container->add(Acme\Foo::class)->addArgument(Acme\BarInterface::class);
// Acme\BarInterface is added as an alias with Acme\BarA as the concrete implementation,
// this could be swapped to Acme\BarB and that would be injected instead
$container->add(Acme\BarInterface::class, Acme\BarA::class);

$foo = $container->get(Acme\Foo::class);

var_dump($foo instanceof Acme\Foo);               // true
var_dump($foo->bar instanceof Acme\BarInterface); // true
var_dump($foo->bar instanceof Acme\BarA);         // true
var_dump($foo->bar instanceof Acme\BarB);         // false
~~~

Container has many more features and ways it can be configured to implement dependency injection, continue reading the documentation to find out more.

## Questions?

Container was created by Phil Bennett. Find him on Twitter at [@philipobenito](https://twitter.com/philipobenito).
