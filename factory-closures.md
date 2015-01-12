---
layout: default
permalink: /factory-closures/
title: Factory Closures
---

# Factory Closures

The most performant way to use Orno\Di is to use factory closures/anonymous functions to build your objects. By registering a closure that returns a fully configured object, when resolved, your object will be lazy loaded as and when you need access to it.

Consider an object `Foo` that depends on another object `Bar`. The following will return an instance of `Foo` containing a member `bar` that contains an instance of `Bar`.

~~~ php
class Foo
{
    public $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}

class Bar
{
    // ..
}

$container = new League\Container\Container;

$container->add('foo', function() {
    $bar = new Bar;
    return new Foo($bar);
});

$foo = $container->get('foo');
~~~
