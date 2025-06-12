---
layout: post
title: Auto Wiring
sections:
    Introduction: introduction
    Usage: usage
---
## Introduction

> Note: Auto wiring is turned off by default but can be turned on by registering the `ReflectionContainer` as a container delegate. Read below and see the [documentation on delegate containers](/5.x/delegate-containers/).

Container has the power to automatically resolve your objects and all of their dependencies recursively by inspecting the type hints of your constructor arguments. Unfortunately, this method of resolution has a few small limitations but is great for smaller apps. First of all, you are limited to constructor injection and secondly, all injections must be objects.

## Usage

Consider the code below.

~~~ php
<?php 

namespace Acme;

class Foo
{
    public function __construct(
        public readonly Bar $bar,
        public readonly Baz $baz
    ) {}
}

class Bar
{
    public function __construct(
        public readonly Bam $bam
    ) {}
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

$bam = new Acme\Bam();
$baz = new Acme\Baz();
$bar = new Acme\Bar($bam);
$foo = new Acme\Foo($bar, $baz);
~~~

With nested dependencies, this can become quite cumbersome and hard to keep track of. With the container, to return a fully configured instance of `Acme\Foo` it is as simple as requesting `Acme\Foo` from the container.

~~~ php
<?php 

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

$container = new League\Container\Container();

// register the reflection container as a delegate to enable auto wiring
$container->delegate(
    new League\Container\ReflectionContainer(cacheResolutions: true)
);

$fooOne = $container->get(Acme\Foo::class);
$fooTwo = $container->get(Acme\Foo::class);

var_dump($fooOne === $fooTwo); // true
~~~

## Advanced Auto-Wiring with Modern PHP

Auto-wiring works excellently with modern PHP features like union types and promoted constructor properties:

~~~ php
<?php 

interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): void;
}

class RedisCache implements CacheInterface
{
    public function get(string $key): mixed { /* ... */ }
    public function set(string $key, mixed $value): void { /* ... */ }
}

class DatabaseLogger
{
    public function log(string $message): void { /* ... */ }
}

class FileLogger
{
    public function log(string $message): void { /* ... */ }
}

// Service using union types and nullable dependencies
class AdvancedService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly DatabaseLogger|FileLogger $logger,
        private readonly ?string $apiKey = null
    ) {}

    public function process(array $data): array
    {
        // Use cache and logger...
        return match($this->logger::class) {
            DatabaseLogger::class => $this->processWithDatabase($data),
            FileLogger::class => $this->processWithFile($data),
        };
    }

    private function processWithDatabase(array $data): array { return $data; }
    private function processWithFile(array $data): array { return $data; }
}

$container = new League\Container\Container();
$container->delegate(new League\Container\ReflectionContainer());

// Register implementations
$container->add(CacheInterface::class, RedisCache::class);
$container->add(DatabaseLogger::class);

$service = $container->get(AdvancedService::class);
~~~

**Note:** The reflection container, by default, will resolve what you are requesting every time you request it.
