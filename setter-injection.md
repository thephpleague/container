---
layout: default
permalink: /setter-injection/
title: Setter Injection
---

# Setter Injection

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

$container = new Orno\Di\Container;

$container->add('session', 'Session')
          ->withMethodCall('setStorage', ['Storage'])
          ->withMethodCall('setSessionKey', ['my_super_secret_session_key']);

$session = $container->get('session');
~~~

This has the added benefit of being able to manipulate the behaviour of the object with optional setters. Only call the methods you need for this instance of the object.
