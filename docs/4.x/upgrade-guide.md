---
layout: post
title: Upgrade Guide
sections:
    Introduction: introduction
    3.x to 4.x: 3x-to-4x
    2.x to 4.x: 2x-to-4x
---
## Introduction

Here we will attempt to provide as clear a guide as possible for upgrading to the latest version of the package.

If you notice anything missing from this page, please create an issue, or pull request.

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
