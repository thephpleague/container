---
layout: default
permalink: /getting-started/
title: Getting Started
---

# Getting Started

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

var_dump($service instanceof Acme\Service\SomeService); // true
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

var_dump($service instanceof Acme\Service\SomeService); // true
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

var_dump($service instanceof Acme\Service\SomeService); // true
var_dump($service1 === $service2); // true
~~~

~~~ php
<?php

$container = new League\Container\Container;

// register the service as an instance against an alias
$container->add('service', new Acme\Service\SomeService);

// you retrieve the service in exactly the same way, however, each time you
// call `get` you will retrieve the same instance
$service1 = $container->get('Acme\Service\SomeService');
$service2 = $container->get('Acme\Service\SomeService');

var_dump($service instanceof Acme\Service\SomeService); // true
var_dump($service1 === $service2); // true
~~~
