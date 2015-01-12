---
layout: default
permalink: /constructor-injection/
title: Constructor Injection
---

# Constructor Injection

The container can be used to register objects and inject constructor arguments such as dependencies or config items.

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
