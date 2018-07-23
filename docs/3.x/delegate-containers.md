---
layout: default
title: Delegate Containers
---

# Delegate Containers

Delegate containers are a way to allow you to register one or multiple backup containers that will be used to attempt the resolution of services when they cannot be resolved via this container.

A delegate must be a [PSR-11](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md) implementation and can be registered using the `delegate` method.

~~~ php
<?php

namespace Acme\Container;

use Psr\Container\ContainerInterface;

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
