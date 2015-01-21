---
layout: default
permalink: /configuration/
title: Configuration
---

# Configuration

As your project grows, so will your dependency map. At this point it may be worth abstracting your mappings in to a config file. You can store your mappings in an array, or any object implementing the `ArrayAccess` interface.

> **Note:** When using an array, or other ArrayAccess object, the mappings **must** be under a key named `di`.

~~~ php
class Foo
{
    public $bar;

    public $baz;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function setBaz(Baz $baz)
    {
        $this->baz = $baz;
    }
}

class Bar
{
    // ..
}

class Baz
{
    // ..
}
~~~

To map the above code you may do the following.

~~~ php
<?php // array_config.php

return [
    'Foo' => [
        'class'     => 'Foo',
        'arguments' => [
            'Bar'
        ],
        'methods'   => [
            'setBaz' => ['Baz']
        ]
    ],
    'Bar' => 'Bar',
    'Baz' => 'Baz'
];
~~~

~~~ php
$config = [
    'di' => require 'path/to/config/array_config.php',
];

$container = new League\Container\Container($config);

$foo = $container->get('Foo');
~~~
