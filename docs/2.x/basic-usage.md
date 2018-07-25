---
layout: post
title: Basic Usage
sections:
    Getting Started: getting-started
    Constructor Injection: constructor-injection
    Setter Injection: setter-injection
    Factory Closures: factory-closures
---
## Getting Started

Container allows you to register services, with or without their dependencies for later retrieval.

~~~ php
<?php

namespace Acme\Service;

class SomeService
{
    // ...
}
~~~

There are several ways to now register this service with the container.

~~~ php
<?php

$container = new League\Container\Container;

// register the service as a prototype against an alias
$container->add('service', 'Acme\Service\SomeService');

// now to retrieve this service we can just retrieve the alias
// each time we `get` the service it will be a new instance
$service1 = $container->get('service');
$service2 = $container->get('service');

var_dump($service1 instanceof Acme\Service\SomeService); // true
var_dump($service1 === $service2); // false
~~~

~~~ php
<?php

$container = new League\Container\Container;

// register the service as a prototype against the fully qualified classname
$container->add('Acme\Service\SomeService');

// now to retrieve this service we can just retrieve the classname
// each time we `get` the service it will be a new instance
$service1 = $container->get('Acme\Service\SomeService');
$service2 = $container->get('Acme\Service\SomeService');

var_dump($service1 instanceof Acme\Service\SomeService); // true
var_dump($service1 === $service2); // false
~~~

There may be occasions where you wish the service to be the same instance each time you retrieve it. There are two ways to achieve this, declare it as shared, or register a ready built instance of an object.

~~~ php
<?php

$container = new League\Container\Container;

// register the service as shared against the fully qualified classname
$container->share('Acme\Service\SomeService');

// you retrieve the service in exactly the same way, however, each time you
// call `get` you will retrieve the same instance
$service1 = $container->get('Acme\Service\SomeService');
$service2 = $container->get('Acme\Service\SomeService');

var_dump($service1 instanceof Acme\Service\SomeService); // true
var_dump($service1 === $service2); // true
~~~

~~~ php
<?php

$container = new League\Container\Container;

// register the service as an instance against an alias
$container->add('service', new Acme\Service\SomeService);

// you retrieve the service in exactly the same way, however, each time you
// call `get` you will retrieve the same instance
$service1 = $container->get('service');
$service2 = $container->get('service');

var_dump($service1 instanceof Acme\Service\SomeService); // true
var_dump($service1 === $service2); // true
~~~

## Constructor Injection

Container can be used to register objects and inject constructor arguments such as dependencies or config items.

For example, if we have a `Session` object that depends on an implementation of a `StorageInterface` and also requires a session key string. We could do the following:

~~~ php
<?php

namespace Acme\Session;

class Session
{
    public $storage;

    public $sessionKey;

    public function __construct(StorageInterface $storage, $sessionKey)
    {
        $this->storage    = $storage;
        $this->sessionKey = $sessionKey;
    }
}
~~~

~~~ php
<?php

namespace Acme\Session;

interface StorageInterface
{
    // ..
}
~~~

~~~ php
<?php

namespace Acme\Session;

class Storage implements StorageInterface
{
    // ..
}
~~~

~~~ php
<?php

$container = new League\Container\Container;

// by registering the storage implementation as an alias of it's interface it
// is easy to swap out for other implementations
$container->add('Acme\Session\StorageInterface', 'Acme\Session\Storage');

$container
    ->add('Acme\Session\Session')
    ->withArgument('Acme\Session\StorageInterface')
    ->withArgument(new League\Container\Argument\RawArgument('my_super_secret_session_key'));

$session = $container->get('Acme\Session\Session');

var_dump($session instanceof Acme\Session\Session); // true
var_dump($session->storage instanceof Acme\Session\Storage); // true
var_dump($session->sessionKey === 'my_super_secret_session_key'); // true
~~~

## Setter Injection

If you prefer setter injection to constructor injection, a few minor alterations can be made to accommodate this.

~~~ php
<?php

namespace Acme\Session;

class Session
{
    public $storage;

    public $sessionKey;

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function setSessionKey($sessionKey)
    {
        $this->sessionKey = $sessionKey;
    }
}
~~~

~~~ php
<?php

namespace Acme\Session;

interface StorageInterface
{
    // ..
}
~~~

~~~ php
<?php

namespace Acme\Session;

class Storage implements StorageInterface
{
    // ..
}
~~~

~~~ php
<?php

$container = new League\Container\Container;

// by registering the storage implementation as an alias of it's interface it
// is easy to swap out for other implementations
$container->add('Acme\Session\StorageInterface', 'Acme\Session\Storage');

$container
    ->add('Acme\Session\Session')
    ->withMethodCall(
        'setStorage',
        [
            'Acme\Session\StorageInterface'
        ]
    )
    ->withMethodCall(
        'setSessionKey',
        [
            new League\Container\Argument\RawArgument('my_super_secret_session_key')
        ]
    );

$session = $container->get('Acme\Session\Session');

var_dump($session instanceof Acme\Session\Session); // true
var_dump($session->storage instanceof Acme\Session\Storage); // true
var_dump($session->sessionKey === 'my_super_secret_session_key'); // true
~~~

This has the added benefit of being able to manipulate the behaviour of the object with optional setters. Only call the methods you need for this instance of the object.

## Factory Closures

The most performant way to use Container is to use factory closures/anonymous functions to build your objects. By registering a closure that returns a fully configured object, when resolved, your object will be lazy loaded as and when you need access to it.

Consider an object `Foo` that depends on another object `Bar`. The following will return an instance of `Foo` containing a member `bar` that contains an instance of `Bar`.

~~~ php
<?php

namespace Acme;

class Foo
{
    public $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}
~~~

~~~ php
<?php

namespace Acme;

class Bar
{
    // ..
}
~~~

~~~ php
<?php

$container = new League\Container\Container;

$container->add('foo', function() {
    $bar = new Acme\Bar;
    return new Acme\Foo($bar);
});

$foo = $container->get('foo');

var_dump($foo instanceof Acme\Foo); // true
var_dump($foo->bar instanceof Acme\Bar); // true
~~~
