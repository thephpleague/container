---
layout: post
title: Inflectors
sections:
    Deprecation Notice: deprecation-notice
    Migration Guide: migration-guide
    Usage: usage
---

## Deprecation Notice

> **DEPRECATED:** Inflectors are deprecated as of v5.2 and will be removed in v6.0. Use `afterResolve()` or the [event system](/docs/unstable/events) instead.

## Migration Guide

### Using afterResolve() (recommended)

`afterResolve()` is a drop-in replacement for the most common inflector patterns. The callback receives the resolved object directly:

**Callback form:**
~~~ php
<?php

// Before
$container->inflector(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));

// After
$container->afterResolve(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));
~~~

**Method invocation:**
~~~ php
<?php

// Before
$container->inflector(LoggerAwareInterface::class)
    ->invokeMethod('setLogger', [Logger::class]);

// After
$container->afterResolve(LoggerAwareInterface::class, function (object $service) use ($container) {
    $service->setLogger($container->get(Logger::class));
});
~~~

**Property setting:**
~~~ php
<?php

// Before
$container->inflector(DatabaseAwareInterface::class)
    ->setProperty('connection', Database::class);

// After
$container->afterResolve(DatabaseAwareInterface::class, function (object $service) use ($container) {
    $service->connection = $container->get(Database::class);
});
~~~

**Multiple method calls:**
~~~ php
<?php

// Before
$container->inflector(TimestampableInterface::class)
    ->invokeMethods([
        'setCreatedAt' => [new DateTime()],
        'setUpdatedAt' => [new DateTime()]
    ]);

// After
$container->afterResolve(TimestampableInterface::class, function (object $service) {
    $service->setCreatedAt(new DateTime());
    $service->setUpdatedAt(new DateTime());
});
~~~

### Using the full event API

For advanced use cases such as replacing resolved objects, use `listen()` directly:

~~~ php
<?php

use League\Container\Event\ServiceResolvedEvent;

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $event->setResolved(new CachedRepository($event->getResolved()));
})->forType(RepositoryInterface::class);
~~~

See the [events documentation](/docs/unstable/events) for the full API.

---

## Usage

> The following documents the deprecated inflector API for reference. New code should use `afterResolve()` instead.

Inflectors allow you to define the manipulation of an object of a specific type as the final step before it is returned by the container.

This is useful when you want to invoke a method on all objects that implement a specific interface.

~~~ php
<?php

$container = new League\Container\Container();

$container->add(Acme\Logger::class);
$container->add(Acme\LoggerAwareClass::class);
$container->add(Acme\Other\LoggerAwareClass::class);

$container
    ->inflector(LoggerAwareInterface::class)
    ->invokeMethod('setLogger', [Acme\Logger::class])
;
~~~

Now instead of adding a method call to each class individually we can simply define an inflector to invoke the method for every class of that type.
