---
layout: post
title: Upgrade Guide
sections:
    Introduction: introduction
    5.x to 6.0: 5x-to-60
    5.1 to 5.2: 51-to-52
    3.x to 4.x: 3x-to-4x
    2.x to 4.x: 2x-to-4x
---
## Introduction

Here we will attempt to provide as clear a guide as possible for upgrading to the latest version of the package.

If you notice anything missing from this page, please create an issue, or pull request.

## 5.x to 6.0

### PHP Version

PHP 8.3+ is now required. PHP 8.1 and 8.2 have reached end of life and are no longer supported.

### Inflector Subsystem Removed

The entire inflector subsystem has been removed. `Container::inflector()` was deprecated in 5.2 and is now gone, along with `InflectorInterface`, `InflectorAggregate`, and `InflectorAggregateInterface`.

The `inflector()` method has also been removed from `DefinitionContainerInterface`. If you have custom implementations of this interface, remove the `inflector()` method.

Use `Container::afterResolve()` as a drop-in replacement:

~~~php
// Before (5.x)
$container->inflector(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));

// After (6.x)
$container->afterResolve(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));
~~~

For method invocation and property setting patterns, the callback form covers all use cases:

~~~php
// Before (5.x) - method invocation
$container->inflector(LoggerAwareInterface::class)
    ->invokeMethod('setLogger', [Logger::class]);

// After (6.x)
$container->afterResolve(LoggerAwareInterface::class, function (object $service) use ($container) {
    $service->setLogger($container->get(Logger::class));
});

// Before (5.x) - property setting
$container->inflector(DatabaseAwareInterface::class)
    ->setProperty('connection', Database::class);

// After (6.x)
$container->afterResolve(DatabaseAwareInterface::class, function (object $service) use ($container) {
    $service->connection = $container->get(Database::class);
});
~~~

Note: there is no direct event system equivalent for the `setProperty()` inflector pattern. Use constructor injection or the callback form shown above.

See the [events documentation](/unstable/events/) for the full event API.

### Container Constructor Change

The `Container` constructor no longer accepts an `InflectorAggregateInterface` parameter. If you were passing arguments positionally to the constructor, update your call:

~~~php
// Before (5.x)
$container = new Container($definitions, $providers, $inflectors);

// After (6.x)
$container = new Container($definitions, $providers);
~~~

### ContainerAwareInterface Change

`ContainerAwareInterface::setContainer()` now returns `static` instead of `ContainerAwareInterface`. If you have custom implementations, update the return type:

~~~php
// Before (5.x)
public function setContainer(DefinitionContainerInterface $container): ContainerAwareInterface

// After (6.x)
public function setContainer(DefinitionContainerInterface $container): static
~~~

### New Features

Several new features have been added in 6.0:

- **[Contextual binding](/unstable/contextual-binding/)** allows different implementations of the same interface to be injected depending on the consumer class, via `addContextualArgument()` on definitions.
- **Improved error messages** with "did you mean?" suggestions on `NotFoundException`, runtime circular dependency detection, and resolution chain reporting in nested failures.
- **[Container introspection](/unstable/introspection/)** via `getDefinitionIds()` and `getServiceProviderIds()` on the `Container` class, plus a `--dump` flag on the compile CLI.
- **[`#[Shared]` attribute](/unstable/attribute-resolution/#using-shared)** for declaring singleton intent at the class level when auto-wired via `ReflectionContainer`.

### DefinitionInterface Changes

`DefinitionInterface` has two new methods in 6.0:

- `addContextualArgument(string $abstract, string|object $concrete): DefinitionInterface`
- `getContextualArguments(): array`

If you have custom implementations of `DefinitionInterface`, add these methods.

### ServiceProviderInterface Changes

`ServiceProviderInterface` has a new method in 6.0:

- `getProvidedIds(): array`

`AbstractServiceProvider` provides a default implementation returning `[]`. Override this method if you want your provider's services to appear in `Container::getServiceProviderIds()`.

### Coding Standard

The project coding standard has changed from PSR-12 to [PER Coding Style 2.0](https://www.php-fig.org/per/coding-style/). Contributors should run `composer test:style` to check compliance or `vendor/bin/php-cs-fixer fix` to auto-fix.

### Testing Framework

Tests have migrated from PHPUnit to [Pest v4](https://pestphp.com/). Run tests with `composer test:unit` or `vendor/bin/pest`.

## 5.1 to 5.2

### Event System

A new event system replaces inflectors. The event system hooks into the container lifecycle at four points: definition registration, pre-resolution, post-definition-resolution, and post-service-resolution.

### Inflectors are deprecated

`Container::inflector()` now triggers `E_USER_DEPRECATED`. Use `Container::afterResolve()` as a drop-in replacement:

~~~php
// Before
$container->inflector(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));

// After
$container->afterResolve(LoggerAwareInterface::class, fn($obj) => $obj->setLogger($logger));
~~~

For method invocation and property setting patterns, the callback form covers all use cases:

~~~php
// Before
$container->inflector(LoggerAwareInterface::class)
    ->invokeMethod('setLogger', [Logger::class]);

// After
$container->afterResolve(LoggerAwareInterface::class, function (object $service) use ($container) {
    $service->setLogger($container->get(Logger::class));
});
~~~

### DefinitionInterface changes

`DefinitionInterface` now includes `getTags(): array`. If you have custom implementations of `DefinitionInterface`, add this method.

### EventAwareContainerInterface removed

`EventAwareContainerInterface` has been removed. `DefinitionContainerInterface` no longer extends it. The event system is available on the concrete `Container` class via `EventAwareTrait`. If you were type-hinting against `EventAwareContainerInterface`, use the concrete `Container` class instead.

### Shared tag

Shared definitions (registered via `addShared()`) now automatically receive a `'shared'` tag. This means `$definition->hasTag('shared')` returns `true` for shared definitions. If you were using a custom `'shared'` tag, this may conflict.

See the [events documentation](/unstable/events/) for the full API.

## 3.x to 4.x

### PHP Version

PHP 7.2+ is now required.

### API Changes

#### Container and Definitions

`Container::add` no longer accepts a 3rd argument `$shared`. To define a shared item, use `Container::addShared`.
~~~php
// old
$container->add($id, $concrete, true);
// new
$container->addShared($id, $concrete);
~~~
If a container is set to be shared by default, to define a non-shared item, use `Definition::setShared(false)`.
~~~php
$container->defaultToShared();
// old
$container->add($id, $concrete, false);
// new
$container->add($id, $concrete)->setShared(false);
~~~
The convenience method `Container::share` has been replaced with `Container::addShared`.
~~~php
// old
$container->share($id, $concrete);
// new
$container->addShared($id, $concrete);
~~~
`Container::get` no longer accepts a 2nd argument `$new`. To force resolution of a new item, use `Container::getNew`.
~~~php
// old
$container->get($id, true);
// new
$container->getNew($id);
~~~

#### Service Providers

`ServiceProviderInterface` methods now declare a return type of `void`.
~~~php
// old
public function boot()
{
    // ...
}

public function register()
{
    // ...
}

// new
public function boot(): void
{
    // ...
}

public function register(): void
{
    // ...
}
~~~
The distinction between `AbstractServiceProvider::getContainer` and `AbstractServiceProvider::getLeagueContainer` has been removed. Always just use `AbstractServiceProvider::getContainer`.
~~~php
public function register(): void
{
    // old
    $this->getLeagueContainer()->add($id, $concrete);
    // new
    $this->getContainer()->add($id, $concrete);
}
~~~
Service providers are now expected to implement `bool ServiceProviderInterface::provides` rather than defining a `array $provides` property.
~~~php
class MyServiceProvider extends League\Container\ServiceProvider\AbstractServiceProvider
{
    // old
    protected $provides = [
        MyService::class,
        AnotherService::class,
    ];
    
    // new example, should return true if the service provider provides
    // a service for the given alias $id
    public function provides(string $id): bool
    {
        $services = [
            MyService::class,
            AnotherService::class,
        ];
        
        return in_array($id, $services);
    }
}
~~~

#### Argument Wrappers

Any use of `ClassName`/`ClassNameInterface` or `RawArgument`/`RawArgumentInterface` should be removed in favour of `ResolvableArgument` and `LiteralArgument`.
~~~php
// old
$container->add('string', new League\Container\Argument\RawArgument('a string'));
// new
$container->add('string', new League\Container\Argument\Literal\StringArgument('a string'));
~~~

See [full documentation](/4.x/argument-types/) to determine the best changes for you.

## 2.x to 4.x

### PHP Version

PHP 7.2+ is now required.

### API Changes

#### Container and Definitions

`Container::add` no longer accepts a 3rd argument `$shared`. To define a shared item, use `Container::addShared`.
~~~php
// old
$container->add($id, $concrete, true);
// new
$container->addShared($id, $concrete);
~~~
The convenience method `Container::share` has been replaced with `Container::addShared`.
~~~php
// old
$container->share($id, $concrete);
// new
$container->addShared($id, $concrete);
~~~
`Container::call` has been removed, use `ReflectionContainer::call` instead.
~~~php
// old
$container->call($callable, $args);
// new
(new League\Container\ReflectionContainer())->call($callable, $args);
~~~
`Container::hasInDelegate` has been removed, use `Container::has`, or specifically, `DelegateContainer::has` if you need to determine where the service is coming from.
~~~php
$delegateContainer = new DelegateContainer();
$container->delegate($delegateContainer);
// old
$container->hasInDelegate($id);
// new
$container->has($id); // checks main container and then all delegates
$delegateContainer->has($id); // check specifically in the delegate
~~~

`Container::get` no longer accepts a 2nd argument `$args` with an array of runtime arguments to pass to the service on resolution. 

> Arguments should be defined at the same time the service is defined. If you are relying on defining arguments at runtime, you are likely using service location, this is not what the container is designed for and you shouldn't.

If you absolutely need to do this, a better way would be to define your service with no arguments, extend it, and add the arguments to the definition.
~~~php
// old
$container->get($id, $args);
// new
$container->extend($id)->addArguments($args);
$container->get($id);
~~~
Be aware though that these arguments will be used for subsequent resolutions of that service, if you need to resolve the service multiple times with different arguments, either define multiple aliases for the same service, or first extend the service, and clone it before adding the arguments.
~~~php
$originalDefinition = $container->extend($id);
$clonedDefinition = clone $originalDefinition;
$clonedDefinition->addArguments($args);

$clonedDefinition->resolve();
~~~

All `withX` methods have been replaced with `addX` to better describe the behaviour.
~~~php
// old
$container->add($id, $concrete)->withArgument($arg);
$container->add($id, $concrete)->withArguments($args);
$container->add($id, $concrete)->withMethodCall('method', [$args]);
$container->add($id, $concrete)->withMethodCalls(['method' => [$args]]);
// new
$container->add($id, $concrete)->addArgument($arg);
$container->add($id, $concrete)->addArguments($args);
$container->add($id, $concrete)->addMethodCall('method', [$args]);
$container->add($id, $concrete)->addMethodCalls(['method' => [$args]]);
~~~

#### Service Providers

`ServiceProviderInterface` methods now declare a return type of `void`.
~~~php
// old
public function boot()
{
    // ...
}

public function register()
{
    // ...
}

// new
public function boot(): void
{
    // ...
}

public function register(): void
{
    // ...
}
~~~
The distinction between `AbstractServiceProvider::getContainer` and `AbstractServiceProvider::getLeagueContainer` has been removed. Always just use `AbstractServiceProvider::getContainer`.
~~~php
public function register(): void
{
    // old
    $this->getLeagueContainer()->add($id, $concrete);
    // new
    $this->getContainer()->add($id, $concrete);
}
~~~
Service providers are now expected to implement `bool ServiceProviderInterface::provides` rather than defining a `array $provides` property.
~~~php
class MyServiceProvider extends League\Container\ServiceProvider\AbstractServiceProvider
{
    // old
    protected $provides = [
        MyService::class,
        AnotherService::class,
    ];
    
    // new example, should return true if the service provider provides
    // a service for the given alias $id
    public function provides(string $id): bool
    {
        $services = [
            MyService::class,
            AnotherService::class,
        ];
        
        return in_array($id, $services);
    }
}
~~~

#### Internals

Sub-type definition classes `CallableDefinition` and `ClassDefinition` have been removed and are now just `League\Container\Definition\Definition`, this will only require action if you build definitions manually or have created your own definition classes that extend these.
