---
layout: default
title: Factory Closures
---

# Factory Closures

The most performant way to use Container is to use factory closures/anonymous functions to build your objects. By registering a closure that returns a fully configured object, when resolved, your object will be lazy loaded as and when you need access to it.

Consider an object `Foo` that depends on another object `Bar`. The following will return an instance of `Foo` containing a member `bar` that contains an instance of `Bar`.

~~~ php
<?php

namespace Acme;

class Foo
{
    public $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}
~~~

~~~ php
<?php

namespace Acme;

class Bar
{
    // ..
}
~~~

~~~ php
<?php

$container = new League\Container\Container;

$container->add('foo', function() {
    $bar = new Acme\Bar;
    return new Acme\Foo($bar);
});

$foo = $container->get('foo');

var_dump($foo instanceof Acme\Foo); // true
var_dump($foo->bar instanceof Acme\Bar); // true
~~~
