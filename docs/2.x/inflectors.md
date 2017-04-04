---
layout: default
title: Inflectors
---

# Inflectors

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
