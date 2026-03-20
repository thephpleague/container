---
layout: post
title: Contextual Binding
sections:
    Introduction: introduction
    Usage: usage
    How It Works: how-it-works
    Compilation Support: compilation-support
    Limitations: limitations
---
## Introduction

Contextual binding allows you to bind different implementations of an interface depending on which class is consuming it. This is useful when two services depend on the same abstraction but require different concrete implementations.

Without contextual binding, you would need to register separate aliases or use the `#[Inject]` attribute on each consumer. Contextual binding keeps your domain classes free of container-specific annotations.

## Usage

Consider two services that both depend on `CacheInterface`, but need different cache backends:

~~~php
<?php

namespace Acme;

interface CacheInterface
{
    public function get(string $key): mixed;
}

class FileCache implements CacheInterface
{
    public function get(string $key): mixed { /* ... */ }
}

class RedisCache implements CacheInterface
{
    public function get(string $key): mixed { /* ... */ }
}

class LogService
{
    public function __construct(
        public readonly CacheInterface $cache
    ) {}
}

class ApiService
{
    public function __construct(
        public readonly CacheInterface $cache
    ) {}
}
~~~

Register each consumer with `addContextualArgument()` to specify which implementation it should receive:

~~~php
<?php

$container = new League\Container\Container();

$container->add(Acme\FileCache::class);
$container->add(Acme\RedisCache::class);

$container->add(Acme\LogService::class)
    ->addContextualArgument(Acme\CacheInterface::class, Acme\FileCache::class);

$container->add(Acme\ApiService::class)
    ->addContextualArgument(Acme\CacheInterface::class, Acme\RedisCache::class);

$log = $container->get(Acme\LogService::class);
$api = $container->get(Acme\ApiService::class);

var_dump($log->cache instanceof Acme\FileCache);  // true
var_dump($api->cache instanceof Acme\RedisCache);  // true
~~~

### Multiple Contextual Arguments

A single definition can have multiple contextual arguments:

~~~php
<?php

$container->add(Acme\OrderProcessor::class)
    ->addContextualArgument(Acme\CacheInterface::class, Acme\RedisCache::class)
    ->addContextualArgument(Acme\LoggerInterface::class, Acme\FileLogger::class);
~~~

### Shared Definitions

Contextual binding works with shared (singleton) definitions:

~~~php
<?php

$container->addShared(Acme\LogService::class)
    ->addContextualArgument(Acme\CacheInterface::class, Acme\FileCache::class);

$log1 = $container->get(Acme\LogService::class);
$log2 = $container->get(Acme\LogService::class);

var_dump($log1 === $log2); // true
~~~

### Object Instances

You can pass an object instance directly as the contextual concrete:

~~~php
<?php

$redisCache = new Acme\RedisCache('redis://localhost:6379');

$container->add(Acme\ApiService::class)
    ->addContextualArgument(Acme\CacheInterface::class, $redisCache);
~~~

## How It Works

When a definition has contextual arguments and no explicit constructor arguments, the container reflects the constructor of the concrete class. For each parameter:

1. If the parameter's type matches a key in the contextual arguments map, the contextual concrete is used
2. Otherwise, the parameter is resolved through the container as normal (standard auto-wiring)
3. If the parameter has a default value, that is used as a fallback

Contextual arguments are keyed by the fully qualified class or interface name (leading backslashes are normalised automatically).

## Compilation Support

Contextual bindings are fully supported by the [container compilation](/unstable/compilation/) system. The compiler resolves contextual arguments at compile time, emitting direct service references in the generated factory methods. This means there is no runtime overhead for contextual binding in compiled containers.

~~~php
<?php

// In the compiled container, LogService's factory method will contain:
// $this->get('Acme\FileCache') rather than resolving CacheInterface dynamically
~~~

The dependency graph correctly reflects contextual bindings, so cycle detection works as expected.

## Limitations

- Contextual binding only applies to services registered with `add()` or `addShared()`. Pure auto-wired classes resolved through the `ReflectionContainer` delegate without an explicit definition cannot use contextual binding. Register the consumer explicitly if you need contextual arguments.
- Contextual arguments are only consulted when no explicit constructor arguments have been provided via `addArgument()`. If you have explicit arguments, they take precedence.
