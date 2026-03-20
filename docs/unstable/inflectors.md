---
layout: post
title: Inflectors
sections:
    Removed: removed
    Migration Guide: migration-guide
---

## Removed

> **Inflectors have been removed in v6.0.** Use `afterResolve()` or the [event system](/unstable/events/) instead.

The inflector subsystem (`Container::inflector()`, `InflectorInterface`, `InflectorAggregate`, `InflectorAggregateInterface`) has been fully removed. The `inflector()` method has also been removed from `DefinitionContainerInterface`.

## Migration Guide

### Using afterResolve() (recommended)

`afterResolve()` is a drop-in replacement for the most common inflector patterns. The callback receives the resolved object directly:

**Callback form:**
~~~ php
<?php

$container->afterResolve(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));
~~~

**Method invocation:**
~~~ php
<?php

$container->afterResolve(LoggerAwareInterface::class, function (object $service) use ($container) {
    $service->setLogger($container->get(Logger::class));
});
~~~

**Property setting:**
~~~ php
<?php

$container->afterResolve(DatabaseAwareInterface::class, function (object $service) use ($container) {
    $service->connection = $container->get(Database::class);
});
~~~

**Multiple method calls:**
~~~ php
<?php

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

See the [events documentation](/unstable/events/) for the full API.
