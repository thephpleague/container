---
layout: default
permalink: /singletons/
title: Singletons
---

# Singletons

Container allows you to register a single instance of a class that will be always be returned when a particular key is requested.  This is known as a singleton and can be registered as a class name, factory closure, or an existing object.

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

Note that using the singleton method is identical to using Container's `add` method with an additional, optional third parameter (which defaults to `false`) that marks the key as a singleton.  As such, it is possible to use the same fluid interface to define a singleton as you can when adding non-singleton definitions.

~~~ php
$container->singleton('Some\Class');

// ... is identical to

$container->add('Some\Class', null, true);
~~~