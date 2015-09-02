---
layout: default
permalink: /setter-injection/
title: Setter Injection
---

# Setter Injection

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
