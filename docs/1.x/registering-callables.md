---
layout: default
title: Registering Callables
---

# Registering Callables

Container allows you to register callables/invokables and call them either with runtime arguments, defaults stored within the container, or if no arguments are stored, the container will attempt to resolve any arguments automatically with type hints or default values.

~~~ php
$container = new League\Container\Container;

$container->add('Some\Class');

$container->invokable('some_helper_function', function (Some\Class $object) {
    // ...
})->withArgument('Some\Class');

$container->call('some_helper_function');
~~~

To let the container do the auto-resolving magic, you can use the `call` method without `invokable`.

~~~ php
$container->call(function (Some\Class $object) {
    // ...
});

$container->call(function (Some\Class $object, $foo, $baz = 'default') {
}, ['foo' => 'foo_value']);
~~~
