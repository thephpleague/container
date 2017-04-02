---
layout: default
title: Singletons
---

# Singletons

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
