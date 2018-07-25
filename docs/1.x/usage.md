---
layout: post
title: Usage
sections:
    Constructor Injection: constructor-injection
    Setter Injection: setter-injection
    Factory Closures: factory-closures
    Singletons: singletons
    Registering Callables: registering-callables
    Auto Dependency Resolution: auto-dependency-resolution
    Service Providers: service-providers
    Inflectors: inflectors
    Configuration: configuration
---
## Constructor Injection

Container can be used to register objects and inject constructor arguments such as dependencies or config items.

For example, if we have a `Session` object that depends on an implementation of a `StorageInterface` and also requires a session key string. We could do the following:

~~~ php
class Session
{
    protected $storage;

    protected $sessionKey;

    public function __construct(StorageInterface $storage, $sessionKey)
    {
        $this->storage    = $storage;
        $this->sessionKey = $sessionKey;
    }
}

interface StorageInterface
{
    // ..
}

class Storage implements StorageInterface
{
    // ..
}

$container = new League\Container\Container;

$container->add('Storage');

$container->add('session', 'Session')
          ->withArgument('Storage')
          ->withArgument('my_super_secret_session_key');

$session = $container->get('session');
~~~

## Setter Injection

If you prefer setter injection to constructor injection, a few minor alterations can be made to accommodate this.

~~~ php
class Session
{
    protected $storage;

    protected $sessionKey;

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function setSessionKey($sessionKey)
    {
        $this->sessionKey = $sessionKey;
    }
}

interface StorageInterface
{
    // ..
}

class Storage implements StorageInterface
{
    // ..
}

$container = new League\Container\Container;

$container->add('session', 'Session')
          ->withMethodCall('setStorage', ['Storage'])
          ->withMethodCall('setSessionKey', ['my_super_secret_session_key']);

$session = $container->get('session');
~~~

This has the added benefit of being able to manipulate the behaviour of the object with optional setters. Only call the methods you need for this instance of the object.

## Factory Closures

The most performant way to use Container is to use factory closures/anonymous functions to build your objects. By registering a closure that returns a fully configured object, when resolved, your object will be lazy loaded as and when you need access to it.

Consider an object `Foo` that depends on another object `Bar`. The following will return an instance of `Foo` containing a member `bar` that contains an instance of `Bar`.

~~~ php
class Foo
{
    public $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}

class Bar
{
    // ..
}

$container = new League\Container\Container;

$container->add('foo', function() {
    $bar = new Bar;
    return new Foo($bar);
});

$foo = $container->get('foo');
~~~

## Singletons

Container allows you to register a single instance of a class that will always be returned when a particular key is requested. This is known as a singleton and can be registered as a class name, factory closure, or an existing object.

~~~ php
$container = new League\Container\Container;

$container->singleton('Some\Class');

$firstObject = $container->get('Some\Class');
$secondObject = $container->get('Some\Class');

$firstObject === $secondObject; // true (they are the same instance)
~~~

The above example also works with factory closures.

~~~ php
$container->singleton('Some\Class', function(){
	$instance = new Some\Class();
	// ...
	return $instance;
});

$firstObject = $container->get('Some\Class');
$secondObject = $container->get('Some\Class');

$firstObject === $secondObject; // true
~~~

Note that the singleton method is an alias of calling the `add` method with the third parameter set to true. As such, it is possible to define a singleton using the same fluid interface you can use when adding non-singleton definitions.

~~~ php
$container->singleton('Some\Class');

// ... is identical to

$container->add('Some\Class', null, true);
~~~

## Registering Callables

Container allows you to register callables/invokables and call them either with runtime arguments, defaults stored within the container, or if no arguments are stored, the container will attempt to resolve any arguments automatically with type hints or default values.

~~~ php
$container = new League\Container\Container;

$container->add('Some\Class');

$container->invokable('some_helper_function', function (Some\Class $object) {
    // ...
})->withArgument('Some\Class');

$container->call('some_helper_function');
~~~

To let the container do the auto-resolving magic, you can use the `call` method without `invokable`.

~~~ php
$container->call(function (Some\Class $object) {
    // ...
});

$container->call(function (Some\Class $object, $foo, $baz = 'default') {
}, ['foo' => 'foo_value']);
~~~

## Auto Dependency Resolution

Container has the power to automatically resolve your objects and all of their dependencies recursively by inspecting the type hints of your constructor arguments. Unfortunately, this method of resolution has a few small limitations but is great for smaller apps. First of all, you are limited to constructor injection and secondly, all injections must be objects.

~~~ php
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

class Bar
{
    public $bam;

    public function __construct(Bam $bam)
    {
        $this->bam = $bam;
    }
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

In the above code, `Foo` has 2 dependencies `Bar` and `Baz`, `Bar` has a further dependency of `Bam`. Normally you would have to do the following to return a fully configured instance of `Foo`.

~~~ php
$bam = new Bam;
$baz = new Baz;
$bar = new Bar($bam);
$foo = new Foo($bar, $baz);
~~~

With nested dependencies, this can become quite cumbersome and hard to keep track of. With the container, to return a fully configured instance of `Foo` it is as simple as requesting `Foo` from the container.

~~~ php
$container = new League\Container\Container;

$foo = $container->get('Foo');
~~~

## Service Providers

Service providers give the benefit of organising your container definitions along with an increase in performance for larger applications as definitions registered within a service provider are lazily registered.

To build a service provider it is as simple as extending the base service provider and defining what you would like to register.

~~~ php
namespace Acme\ServiceProvider;

use League\Container\ServiceProvider;

class SomeServiceProvider extends ServiceProvider
{
    /**
     * This array allows the container to be aware of
     * what your service provider actually provides,
     * this should contain all alias names that
     * you plan to register with the container
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
     * that you need to
     */
    public function register()
    {
        $this->getContainer()['key'] = 'value';
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
$container->addServiceProvider(new Acme\ServiceProvider\SomeServiceProvider);
$container->addServiceProvider('Acme\ServiceProvider\SomeServiceProvider');
~~~

Now when you want to retrieve one of the items provided by the service provider, it will not actually be registered until it is needed.

## Inflectors

Inflectors allow you to define the manipulation of an object of a specific type as the final step before it is returned by the container.

This is useful for example when you want to invoke a method on all objects that implement a specific interface.

Imagine that you have a `LoggerAwareInterface` and would like to invoke the method called `setLogger` passing in a logger every time a class is retrieved that implements this interface.

~~~ php
$container->register('Some\Logger');
$container->register('Some\LoggerAwareClass'); // implements LoggerAwareInterface
$container->register('Some\Other\LoggerAwareClass'); // implements LoggerAwareInterface

$container->inflector('LoggerAwareInterface')
          ->invokeMethod('setLogger', ['Some\Logger']); // Some\Logger will be resolved via the container
~~~

Now instead of adding a method call to each class individually we can simply define an inflector to invoke the method for every class of that type.

## Configuration

As your project grows, so will your dependency map. At this point it may be worth abstracting your mappings in to a config file. You can store your mappings in an array, or any object implementing the `ArrayAccess` interface.

> **Note:** When using an array, or other ArrayAccess object, the mappings **must** be under a key named `di`.

~~~ php
class Foo
{
    public $bar;

    public $baz;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function setBaz(Baz $baz)
    {
        $this->baz = $baz;
    }
}

class Bar
{
    // ..
}

class Baz
{
    // ..
}
~~~

To map the above code you may do the following.

~~~ php
<?php // array_config.php

return [
    'Foo' => [
        'class'     => 'Foo',
        'arguments' => [
            'Bar'
        ],
        'methods'   => [
            'setBaz' => ['Baz']
        ]
    ],
    'Bar' => 'Bar',
    'Baz' => 'Baz'
];
~~~

~~~ php
$config = [
    'di' => require 'path/to/config/array_config.php',
];

$container = new League\Container\Container($config);

$foo = $container->get('Foo');
~~~
