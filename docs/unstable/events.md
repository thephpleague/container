---
layout: post
title: Events
sections:
    Introduction: introduction
    Event Types: event-types
    Basic Usage: basic-usage
    Event Filtering: event-filtering
    Event Dispatcher: event-dispatcher
    Advanced Features: advanced-features
---

## Introduction

The League Container event system provides a powerful, PSR-14 compatible way to hook into the container's lifecycle and modify services during resolution. Events are dispatched at key points during the container's operation, allowing you to extend functionality without modifying core container code.

The event system is particularly useful when working with delegate containers, such as using `ReflectionContainer` for auto-wiring while maintaining explicit control over core services.

The event system is designed to be:
- **PSR-14 Compatible** - Works with any PSR-14 compliant event dispatcher
- **Flexible** - Multiple filtering options and event types
- **Performant** - Efficient filtering and execution
- **Extensible** - Easy to add custom event logic

## Event Types

The container dispatches four types of events during its lifecycle:

### OnDefineEvent

Fired when a service definition is added to the container.

~~~ php
<?php

use League\Container\Event\OnDefineEvent;

$container->listen(OnDefineEvent::class, function (OnDefineEvent $event) {
    echo "Service '{$event->getId()}' was defined\n";
    $definition = $event->getDefinition();
    // Access definition properties
});
~~~

### BeforeResolveEvent

Fired just before resolution begins. Useful for logging or access control.

~~~ php
<?php

use League\Container\Event\BeforeResolveEvent;

$container->listen(BeforeResolveEvent::class, function (BeforeResolveEvent $event) {
    echo "About to resolve '{$event->getId()}'\n";

    // You can stop propagation to prevent resolution
    if ($event->getId() === 'forbidden.service') {
        $event->stopPropagation();
        throw new AccessDeniedException();
    }
});
~~~

### DefinitionResolvedEvent

Fired after a definition is found but before the object is instantiated.

~~~ php
<?php

use League\Container\Event\DefinitionResolvedEvent;

$container->listen(DefinitionResolvedEvent::class, function (DefinitionResolvedEvent $event) {
    $definition = $event->getResolved();
    echo "Definition found for '{$event->getId()}'\n";
});
~~~

### ServiceResolvedEvent

Fired after a service has been resolved from the container. This is the most commonly used event for service modification. The resolved service can be an object, scalar, array, or other value.

~~~ php
<?php

use League\Container\Event\ServiceResolvedEvent;

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $service = $event->getResolved();
    $service->setResolvedAt(new DateTime());
})->forType(TimestampableInterface::class);
~~~

## Basic Usage

### Listening to Events

The simplest way to listen for events is using the container's `listen()` method:

~~~ php
<?php

use League\Container\Event\ServiceResolvedEvent;

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $object = $event->getResolved();
    $id = $event->getId();

    $object->setLogger(new Logger());
})->forType(LoggerAwareInterface::class);
~~~

### Real-World Example: Auto-Wiring with Custom Configuration

Here's a practical example that combines explicit definitions with auto-wiring:

~~~ php
<?php

use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Container\Event\ServiceResolvedEvent;

// Create containers
$container = new Container();
$container->delegate(new ReflectionContainer());

// Define core infrastructure services explicitly
$container->addShared('config', function() {
    return new Config($_ENV);
});

$container->addShared(LoggerInterface::class, function() use ($container) {
    return new FileLogger($container->get('config')->get('log_path'));
});

// Auto-inject logger into any class that needs it
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use ($container) {
    $event->getResolved()->setLogger($container->get(LoggerInterface::class));
})->forType(LoggerAwareInterface::class);

// Auto-inject configuration into configurable services
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use ($container) {
    $event->getResolved()->setConfig($container->get('config'));
})->forType(ConfigurableInterface::class);

// These classes will be auto-wired and automatically configured:
$userService = $container->get(UserService::class);
$emailService = $container->get(EmailService::class);
$orderProcessor = $container->get(OrderProcessor::class);
~~~

### Direct Event Dispatcher Access

You can also work directly with the event dispatcher:

~~~ php
<?php

$dispatcher = $container->getEventDispatcher();
$dispatcher->addListener(ServiceResolvedEvent::class, $listener);
~~~

## Event Filtering

Events can be filtered to only execute under specific conditions.

### Type-Based Filtering

Listen only for specific object types:

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

// When adding a service
$container->addShared('user.service', UserService::class)
    ->addTag('logging');

// Listen for tagged services
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $logger = $container->get(LoggerInterface::class);
    $event->getResolved()->setLogger($logger);
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

Use custom logic for complex filtering:

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $event->getResolved()->setSpecial(true);
})->forType(UserInterface::class)
  ->where(function (ServiceResolvedEvent $event) {
      return str_starts_with($event->getId(), 'admin.');
  });
~~~

### Combined Filtering

Combine multiple filtering criteria:

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    // This will only execute for UserInterface objects that are tagged 'admin'
    // and have IDs starting with 'admin.'
})->forType(UserInterface::class)
  ->forTag('admin')
  ->where(fn ($event) => str_starts_with($event->getId(), 'admin.'));
~~~

## Event Dispatcher

### PSR-14 Compatibility

The event dispatcher implements PSR-14 interfaces and can work with external event dispatchers:

~~~ php
<?php

use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyDispatcher;
use League\Container\Event\EventDispatcher;

$symfonyDispatcher = new SymfonyDispatcher();
$eventDispatcher = new EventDispatcher($symfonyDispatcher);

$container->setEventDispatcher($eventDispatcher);
~~~

### Stoppable Events

Events implement `StoppableEventInterface` and can halt propagation:

~~~ php
<?php

$container->listen(BeforeResolveEvent::class, function (BeforeResolveEvent $event) {
    if (!$this->isAuthorized($event->getId())) {
        $event->stopPropagation();
        throw new UnauthorizedException();
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

### Dependency Injection in Listeners

Access other container services within event listeners:

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use ($container) {
    $database = $container->get(DatabaseInterface::class);
    $event->getResolved()->setDatabase($database);
})->forType(DatabaseAwareInterface::class);
~~~

### Working with Delegate Containers

Events become particularly powerful when working with delegate containers. Here's a real-world example using `ReflectionContainer` as a delegate for auto-wiring:

~~~ php
<?php

use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Container\Event\ServiceResolvedEvent;

// Main container for explicit definitions
$container = new Container();

// ReflectionContainer as delegate for auto-wiring
$reflectionContainer = new ReflectionContainer();
$container->delegate($reflectionContainer);

// Define core services explicitly in main container
$container->addShared(DatabaseInterface::class, PDODatabase::class);
$container->addShared(LoggerInterface::class, MonologLogger::class);
$container->addShared(CacheInterface::class, RedisCache::class);

// Use events to track which container resolved what
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $source = $event->getDefinition() ? 'main container' : 'reflection delegate';
    error_log("Service '{$event->getId()}' resolved by: {$source}");
});

// Add metadata based on resolution source
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $service = $event->getResolved();
    $source = $event->getDefinition() ? 'main container' : 'reflection delegate';

    if (method_exists($service, 'setMetadata')) {
        $service->setMetadata('resolved_by', $source);
    }
});

// Apply production configuration to explicitly defined services
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $event->getResolved()->setEnvironment('production');
})->where(function (ServiceResolvedEvent $event) {
    return $event->getDefinition() !== null;
});

// Apply development configuration to auto-wired services
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    $service = $event->getResolved();
    $service->setEnvironment('development');
    $service->enableDebugMode(true);
})->forType(ConfigurableInterface::class)
  ->where(function (ServiceResolvedEvent $event) {
      return $event->getDefinition() === null;
  });

// The ReflectionContainer will auto-wire these without explicit definitions:
$userService = $container->get(UserService::class);
$orderProcessor = $container->get(OrderProcessor::class);
$logger = $container->get(LoggerInterface::class);
~~~

#### Plugin System Example

Events with delegates are excellent for plugin architectures:

~~~ php
<?php

// Core application container
$appContainer = new Container();
$pluginContainer = new ReflectionContainer();
$appContainer->delegate($pluginContainer);

// Register core services
$appContainer->addShared(EventDispatcher::class);
$appContainer->addShared(DatabaseInterface::class, AppDatabase::class);

// Event listener for plugin services
$appContainer->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use ($appContainer) {
    $service = $event->getResolved();

    $service->setEventDispatcher($appContainer->get(EventDispatcher::class));
    $service->setDatabase($appContainer->get(DatabaseInterface::class));

    // Register plugin with the application
    $service->register();

    echo "Plugin {$event->getId()} loaded and registered\n";
})->forType(PluginInterface::class)
  ->where(function (ServiceResolvedEvent $event) {
      return $event->getDefinition() === null;
  });

// Plugins are auto-wired through ReflectionContainer
$paymentPlugin = $appContainer->get(PaymentPlugin::class);
$notificationPlugin = $appContainer->get(NotificationPlugin::class);
~~~

#### Testing Environment Setup

Use events to create different behaviors for testing:

~~~ php
<?php

// Production container setup
$container = new Container();
$container->delegate(new ReflectionContainer());

// In testing, override certain services while keeping auto-wiring
if ($environment === 'testing') {
    $container->addShared(EmailService::class, MockEmailService::class);
    $container->addShared(PaymentGateway::class, FakePaymentGateway::class);

    // Log all auto-wired services in tests
    $container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
        if (!$event->getDefinition()) {
            TestLogger::log("Auto-wired: {$event->getId()}");
        }
    });
}

// Classes without explicit definitions are auto-wired
$userController = $container->get(UserController::class);
$emailService = $container->get(EmailService::class);
~~~

### Error Handling

Handle errors gracefully in event listeners:

~~~ php
<?php

$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    try {
        $object = $event->getResolved();
        $object->initialize();
    } catch (Exception $e) {
        // Log error but don't break resolution
        error_log("Failed to initialize {$event->getId()}: " . $e->getMessage());
    }
});
~~~

### Performance Considerations

For optimal performance:

1. **Use type filters** when possible - they're faster than custom filters
2. **Keep listeners focused** - one responsibility per listener
3. **Use tags for grouping** - organize related services
4. **Minimize listener complexity** - keep event handlers simple and fast

~~~ php
<?php

// Faster - uses instanceof check
$container->listen(ServiceResolvedEvent::class, $listener)
    ->forType(UserInterface::class);

// Slower - executes custom function for every event
$container->listen(ServiceResolvedEvent::class, $listener)
    ->where(fn ($e) => $e->getResolved() instanceof UserInterface);
~~~

#### Delegate Container Performance

When using delegate containers, consider these performance tips:

~~~ php
<?php

// Order delegates by likelihood - most used first
$container->delegate($fastContainer);
$container->delegate($reflectionContainer);

// Use events to optimize delegate performance
$container->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
    // Cache expensive reflection-resolved services
    if (!$event->getDefinition() && $event->isNew() === false) {
        $cached = "Reflection service '{$event->getId()}' was cached";
        // Log caching for performance monitoring
    }
});

// Pre-warm frequently used auto-wired services
$container->listen(OnDefineEvent::class, function (OnDefineEvent $event) use ($container) {
    // Pre-resolve commonly used dependencies
    if (in_array($event->getId(), ['Logger', 'Cache', 'Database'])) {
        $container->get($event->getId());
    }
})->forId('Logger', 'Cache', 'Database');
~~~
