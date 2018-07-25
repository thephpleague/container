---
layout: post
title: Advanced
sections:
    Delegate Containers: delegate-containers
    Auto Wiring: auto-wiring
    Inflectors: inflectors
    Service Providers: service-providers
---
## Delegate Containers

Delegates are a way to allow you to register one or multiple backup containers that will be used to attempt the resolution of services when they cannot be resolved via this container.

A delegate must be an implementation of the [container-interop](https://github.com/container-interop/container-interop) project and can be registered using the `delegate` method.

~~~ php
<?php

namespace Acme\Container;

use Interop\Container\ContainerInterface;

class DelegateContainer implements ContainerInterface
{
    // ..
}
~~~

~~~ php
<?php

$container = new League\Container\Container;
$delegate  = new Acme\Container\DelegateContainer;

// this method can be invoked multiple times, each delegate
// is checked in the order that it was registered
$container->delegate($delegate);
~~~

Now that the delegate has been registered, if a service cannot be resolved, the container will resort to the `has` and `get` methods of the delegate to resolve the requested service.

## Auto Wiring

> Note: Auto wiring is turned off by default but can be turned on by registering the `ReflectionContainer` as a container delegate. Read below and see the [documentation on delegates](/2.x/delegates/).

Container has the power to automatically resolve your objects and all of their dependencies recursively by inspecting the type hints of your constructor arguments. Unfortunately, this method of resolution has a few small limitations but is great for smaller apps. First of all, you are limited to constructor injection and secondly, all injections must be objects.

~~~ php
<?php

namespace Acme;

class Foo
{
    public $bar;

    public $baz;

    public function __construct(Bar $bar, Baz $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }
}
~~~

~~~ php
<?php

namespace Acme;

class Bar
{
    public $bam;

    public function __construct(Bam $bam)
    {
        $this->bam = $bam;
    }
}
~~~

~~~ php
<?php

namespace Acme;

class Baz
{
    // ..
}
~~~

~~~ php
<?php

namespace Acme;

class Bam
{
    // ..
}
~~~

In the above code, `Foo` has 2 dependencies `Bar` and `Baz`, `Bar` has a further dependency of `Bam`. Normally you would have to do the following to return a fully configured instance of `Foo`.

~~~ php
<?php

$bam = new Acme\Bam;
$baz = new Acme\Baz;
$bar = new Acme\Bar($bam);
$foo = new Acme\Foo($bar, $baz);
~~~

With nested dependencies, this can become quite cumbersome and hard to keep track of. With the container, to return a fully configured instance of `Foo` it is as simple as requesting `Foo` from the container.

~~~ php
<?php

$container = new League\Container\Container;

// register the reflection container as a delegate to enable auto wiring
$container->delegate(
    new League\Container\ReflectionContainer
);

$foo = $container->get('Acme\Foo');

var_dump($foo instanceof Acme\Foo); // true
var_dump($foo->bar instanceof Acme\Bar); // true
var_dump($foo->baz instanceof Acme\Baz); // true
var_dump($foo->bar->bam instanceof Acme\Bam); // true
~~~

## Inflectors

Inflectors allow you to define the manipulation of an object of a specific type as the final step before it is returned by the container.

This is useful for example when you want to invoke a method on all objects that implement a specific interface.

Imagine that you have a `LoggerAwareInterface` and would like to invoke the method called `setLogger` passing in a logger every time a class is retrieved that implements this interface.

~~~ php
$container->add('Some\Logger');
$container->add('Some\LoggerAwareClass'); // implements LoggerAwareInterface
$container->add('Some\Other\LoggerAwareClass'); // implements LoggerAwareInterface

$container->inflector('LoggerAwareInterface')
          ->invokeMethod('setLogger', ['Some\Logger']); // Some\Logger will be resolved via the container
~~~

Now instead of adding a method call to each class individually we can simply define an inflector to invoke the method for every class of that type.

## Service Providers

Service providers give the benefit of organising your container definitions along with an increase in performance for larger applications as definitions registered within a service provider are lazily registered.

To build a service provider it is as simple as extending the base service provider and defining what you would like to register.

~~~ php
<?php

namespace Acme\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;

class SomeServiceProvider extends AbstractServiceProvider
{
    /**
     * The provides array is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        'key',
        'Some\Controller',
        'Some\Model',
        'Some\Request'
    ];

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {
        $this->getContainer()->add('key', 'value');

        $this->getContainer()->add('Some\Controller')
             ->withArgument('Some\Request')
             ->withArgument('Some\Model');

        $this->getContainer()->add('Some\Request');
        $this->getContainer()->add('Some\Model');
    }
}
~~~

To register this service provider with the container simply pass an instance of your provider or a fully qualified class name to the `League\Container\Container::addServiceProvider` method.

~~~ php
<?php

$container = new League\Container\Container;

$container->addServiceProvider(new Acme\ServiceProvider\SomeServiceProvider);
$container->addServiceProvider('Acme\ServiceProvider\SomeServiceProvider');
~~~

The register method is not invoked until one of the aliases in the `$provides` array is requested by the container, therefore, when we want to retrieve one of the items provided by the service provider, it will not actually be registered until it is needed, this improves performance for larger applications as your dependency map grows.

## Bootable Service Providers

If there is functionality that needs to be run as the service provider is added to the container, for example, setting up inflectors, including config files etc, we can make the service provider bootable by implementing the `BootableServiceProviderInterface`.

~~~ php
<?php

namespace Acme\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class SomeServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @var array
     */
    protected $provides = [
        // ...
    ];

    /**
     * In much the same way, this method has access to the container
     * itself and can interact with it however you wish, the difference
     * is that the boot method is invoked as soon as you register
     * the service provider with the container meaning that everything
     * in this method is eagerly loaded.
     *
     * If you wish to apply inflectors or register further service providers
     * from this one, it must be from a bootable service provider like
     * this one, otherwise they will be ignored.
     */
    public function boot()
    {
        $this->getContainer()
             ->inflector('SomeType')
             ->invokeMethod('someMethod', ['some_arg']);

    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        // ...
    }
}
~~~
