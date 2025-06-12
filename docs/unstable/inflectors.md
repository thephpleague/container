---
layout: post
title: Inflectors
sections:
    Deprecation Notice: deprecation-notice
    Upgrade Guide: upgrade-guide
    Introduction: introduction
    Usage: usage
---

## Deprecation Notice

> **DEPRECATED:** Inflectors are deprecated as of v6.0 and will be removed in v7.0. Please migrate to the new [Event System](/docs/unstable/events) which provides more flexibility and better performance.

## Upgrade Guide

The event system provides a more powerful and flexible replacement for inflectors. Here's how to migrate common inflector patterns:

### Method Invocation

**Before (Inflectors):**
~~~ php
<?php
$container->inflector(LoggerAwareInterface::class)
    ->invokeMethod('setLogger', [Logger::class]);
~~~

**After (Events):**
~~~ php
<?php
use League\Container\Event\ServiceResolvedEvent;

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use ($container) {
    $service = $event->getResolved();
    $logger = $container->get(Logger::class);
    $service->setLogger($logger);
})->forType(LoggerAwareInterface::class);
~~~

### Property Setting

**Before (Inflectors):**
~~~ php
<?php
$container->inflector(DatabaseAwareInterface::class)
    ->setProperty('connection', Database::class);
~~~

**After (Events):**
~~~ php
<?php
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use ($container) {
    $service = $event->getResolved();
    $service->connection = $container->get(Database::class);
})->forType(DatabaseAwareInterface::class);
~~~

### Callback Invocation

**Before (Inflectors):**
~~~ php
<?php
$container->inflector(TimestampableInterface::class)
    ->invokeMethods([
        'setCreatedAt' => [new DateTime()],
        'setUpdatedAt' => [new DateTime()]
    ]);
~~~

**After (Events):**
~~~ php
<?php
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $service = $event->getResolved();
    $service->setCreatedAt(new DateTime());
    $service->setUpdatedAt(new DateTime());
})->forType(TimestampableInterface::class);
~~~

### Benefits of Events Over Inflectors

- **Better Performance**: Events can be filtered more efficiently
- **More Flexible**: Multiple filtering criteria (type, tag, ID, custom logic)
- **PSR-14 Compatible**: Works with standard event dispatchers
- **Better Testing**: Easier to test event listeners in isolation
- **Type Safety**: Better IDE support and static analysis

---
## Introduction

Inflectors allow you to define the manipulation of an object of a specific type as the final step before it is returned by the container.

This is useful for example when you want to invoke a method on all objects that implement a specific interface.

## Usage

Imagine that you have a `LoggerAwareInterface` and would like to invoke the method called `setLogger` passing in a logger every time a class is retrieved that implements this interface.

~~~ php
<?php

declare(strict_types=1);

$container = new League\Container\Container();

$container->add(Acme\Logger::class);
$container->add(Acme\LoggerAwareClass::class); // implements LoggerAwareInterface
$container->add(Acme\Other\LoggerAwareClass::class); // implements LoggerAwareInterface

$container
    ->inflector(LoggerAwareInterface::class)
    ->invokeMethod('setLogger', [Acme\Logger::class]) // Acme\Logger will be resolved via the container
;
~~~

Now instead of adding a method call to each class individually we can simply define an inflector to invoke the method for every class of that type.
