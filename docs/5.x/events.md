---
layout: post
title: Events
sections:
    Introduction: introduction
    Quick Start: quick-start
    Event Types: event-types
    afterResolve: after-resolve
    Event Filtering: event-filtering
    Event Dispatcher: event-dispatcher
    Advanced Features: advanced-features
---

## Introduction

The League Container event system provides a way to hook into the container's lifecycle and modify services during resolution. Events are dispatched at key points during the container's operation, allowing you to extend functionality without modifying core container code.

The event system replaces [inflectors](/5.x/inflectors/), providing a more flexible and powerful alternative. See `afterResolve()` below for the simplest migration path.

The event system is designed to be:
- **Flexible** - Multiple filtering options and event types
- **Performant** - Events are only dispatched when listeners are registered for that event type
- **Extensible** - Easy to add custom event logic

## Quick Start

For the most common use case, applying cross-cutting behaviour to resolved services by type, use `afterResolve()`:

~~~ php
<?php

use League\Container\Container;

$container = new Container();

$container->afterResolve(LoggerAwareInterface::class, function (object $service) use ($logger) {
    $service->setLogger($logger);
});

$container->afterResolve(CacheAwareInterface::class, function (object $service) use ($cache) {
    $service->setCache($cache);
});
~~~

The callback receives the resolved object directly. This is a drop-in replacement for the deprecated `inflector()` method.

For more control, use the full event API with `listen()`.

## Event Types

The container dispatches four types of events during its lifecycle:

### OnDefineEvent

Fired when a service definition is added to the container via `add()` or `addShared()`.

~~~ php
<?php

use League\Container\Event\OnDefineEvent;

$container->listen(OnDefineEvent::class, function (OnDefineEvent $event) {
    echo "Service '{$event->getId()}' was defined\n";
    $definition = $event->getDefinition();
});
~~~

### BeforeResolveEvent

Fired just before resolution begins. Can short-circuit resolution by providing an early result via `setResolved()`.

~~~ php
<?php

use League\Container\Event\BeforeResolveEvent;

$container->listen(BeforeResolveEvent::class, function (BeforeResolveEvent $event) {
    if ($event->getId() === 'forbidden.service') {
        $event->stopPropagation();
        throw new AccessDeniedException();
    }
});
~~~

### DefinitionResolvedEvent

Fired after a definition is found but before the object is instantiated. Can provide an alternative resolution.

~~~ php
<?php

use League\Container\Event\DefinitionResolvedEvent;

$container->listen(DefinitionResolvedEvent::class, function (DefinitionResolvedEvent $event) {
    $definition = $event->getDefinition();
    echo "Definition found for '{$event->getId()}'\n";
});
~~~

### ServiceResolvedEvent

Fired after a service has been fully resolved. This is the most commonly used event for service modification.

~~~ php
<?php

use League\Container\Event\ServiceResolvedEvent;

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $service = $event->getResolved();
    $service->setResolvedAt(new DateTime());
})->forType(TimestampableInterface::class);
~~~

## afterResolve

`afterResolve()` is a convenience method that wraps the event system for the most common use case: applying modifications to resolved services by type. It is the recommended replacement for the deprecated `inflector()` method.

~~~ php
<?php

$container->afterResolve(LoggerAwareInterface::class, function (object $service) use ($logger) {
    $service->setLogger($logger);
});
~~~

### Migrating from inflector()

~~~ php
<?php

// Before
$container->inflector(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));

// After
$container->afterResolve(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));
~~~

### Chaining filters

`afterResolve()` returns an `EventFilter`, so you can add further constraints:

~~~ php
<?php

$container->afterResolve(LoggerAwareInterface::class, function (object $service) use ($logger) {
    $service->setLogger($logger);
})->forTag('needs-logging');
~~~

### Limitations

The callback receives the resolved object directly and can mutate it. To **replace** the resolved object entirely (e.g., wrapping it in a decorator), use the full event API:

~~~ php
<?php

use League\Container\Event\ServiceResolvedEvent;

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $event->setResolved(new CachedRepository($event->getResolved()));
})->forType(RepositoryInterface::class);
~~~

## Event Filtering

Events can be filtered to only execute under specific conditions.

### Type-Based Filtering

Listen only for specific resolved object types (only works with `ServiceResolvedEvent`):

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $event->getResolved()->setCreatedAt(new DateTime());
})->forType(UserInterface::class, AdminInterface::class);
~~~

### Tag-Based Filtering

Listen for services with specific tags:

~~~ php
<?php

$container->addShared('user.service', UserService::class)
    ->addTag('logging');

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use ($container) {
    $event->getResolved()->setLogger($container->get(LoggerInterface::class));
})->forTag('logging');
~~~

### ID-Based Filtering

Listen for specific service IDs:

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $event->getResolved()->setRole('admin');
})->forId('admin.user', 'super.admin');
~~~

### Custom Filtering

Use closures for complex filtering. Multiple `where()` calls compose with AND semantics (all must pass):

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $event->getResolved()->setSpecial(true);
})->forType(UserInterface::class)
  ->where(fn ($event) => str_starts_with($event->getId(), 'admin.'));
~~~

### Combined Filtering

All filter types can be combined. They all must match for the listener to fire:

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $event->getResolved()->setAdminConfig(true);
})->forType(UserInterface::class)
  ->forTag('admin')
  ->where(fn ($event) => str_starts_with($event->getId(), 'admin.'));
~~~

## Event Dispatcher

### Execution Order

When an event is dispatched, listeners are executed in the following order:

1. **Direct listeners** registered via `addListener()` execute first, in registration order
2. **Filtered listeners** registered via `listen()->then()` execute second, in registration order

If a direct listener calls `stopPropagation()`, no filtered listeners will execute for that event.

### Listener Removal

`removeListener()` only removes listeners registered via `addListener()`. Listeners registered via `listen()->then()` (filtered listeners) cannot be individually removed. Use `removeListeners()` to clear all listeners and filters for a given event type.

### Performance

Events are only dispatched when listeners are registered for that specific event type. If no listeners exist for `BeforeResolveEvent`, no `BeforeResolveEvent` objects are created during resolution. This means the event system has near-zero overhead when not in use.

You can check whether listeners exist for a given event type:

~~~ php
<?php

$dispatcher = $container->getEventDispatcher();
$dispatcher->hasListenersFor(ServiceResolvedEvent::class); // true or false
~~~

### Direct Event Dispatcher Access

You can work directly with the event dispatcher for advanced use cases:

~~~ php
<?php

$dispatcher = $container->getEventDispatcher();
$dispatcher->addListener(ServiceResolvedEvent::class, $listener);
~~~

### Stoppable Events

Events implement `StoppableEventInterface` and can halt propagation:

~~~ php
<?php

use League\Container\Event\BeforeResolveEvent;

$container->listen(BeforeResolveEvent::class, function (BeforeResolveEvent $event) {
    if (!isAuthorised($event->getId())) {
        $event->stopPropagation();
        throw new UnauthorisedException();
    }
});
~~~

## Advanced Features

### Object Transformation

Replace resolved objects with decorators or proxies:

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $original = $event->getResolved();
    $cached = new CachedUserRepository($original);
    $event->setResolved($cached);
})->forType(UserRepositoryInterface::class);
~~~

### Working with Delegate Containers

Events are dispatched for services resolved through delegate containers as well. This is useful when using `ReflectionContainer` for auto-wiring:

~~~ php
<?php

use League\Container\Container;
use League\Container\ReflectionContainer;

$container = new Container();
$container->delegate(new ReflectionContainer());

$container->addShared(DatabaseInterface::class, PDODatabase::class);
$container->addShared(LoggerInterface::class, MonologLogger::class);

$container->afterResolve(LoggerAwareInterface::class, function (object $service) use ($container) {
    $service->setLogger($container->get(LoggerInterface::class));
});

$container->afterResolve(DatabaseAwareInterface::class, function (object $service) use ($container) {
    $service->setDatabase($container->get(DatabaseInterface::class));
});

$userService = $container->get(UserService::class);
~~~

### Testing Environment Setup

Use events to create different behaviours for testing:

~~~ php
<?php

$container = new Container();
$container->delegate(new ReflectionContainer());

if ($environment === 'testing') {
    $container->addShared(EmailService::class, MockEmailService::class);
    $container->addShared(PaymentGateway::class, FakePaymentGateway::class);

    $container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
        if (!$event->getDefinition()) {
            TestLogger::log("Auto-wired: {$event->getId()}");
        }
    });
}

$userController = $container->get(UserController::class);
$emailService = $container->get(EmailService::class);
~~~

### Performance Tips

1. **Use `afterResolve()` or `forType()`** for type-based filtering, it uses `instanceof` checks which are faster than custom closures
2. **Keep listeners focused** with one responsibility per listener
3. **Use tags for grouping** related services
4. **Use `forType()` only with `ServiceResolvedEvent`**, it has no effect on other event types

~~~ php
<?php

// Faster: uses instanceof check
$container->listen(ServiceResolvedEvent::class, $listener)
    ->forType(UserInterface::class);

// Slower: executes custom function for every event
$container->listen(ServiceResolvedEvent::class, $listener)
    ->where(fn ($e) => $e->getResolved() instanceof UserInterface);
~~~
