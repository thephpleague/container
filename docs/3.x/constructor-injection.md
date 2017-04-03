---
layout: default
title: Constructor Injection
---

# Constructor Injection

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
