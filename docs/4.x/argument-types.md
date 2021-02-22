---
layout: post
title: Argument Types
sections:
    Introduction: introduction
    Resolvable Arguments: resolvable-arguments
    Literal Arguments: literal-arguments
---
## Introduction

You can define arguments as specific types known to the container, so that it knows what to do with these values without performing further checks. This has a performance benefit, as when one of these types of definitions is found, the value is either instantly returned, or further resolution is performed based on the type given.

## Resolvable Arguments

Resolvable arguments are essentially the default internally for the container. The container will attempt to resolve any argument given to it until it ends up with a value that cannot be resolved further.

Where this becomes useful to explicitly define, is with nested aliases, you can tell the container that when it encounters an argument, that it should explicitly attempt to further resolve that value.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();
$container->add('alias1', new League\Container\Argument\ResolvableArgument('alias2'));
$container->add('alias2', Acme\Foo::class);

$foo = $container->get('alias1');

var_dump($foo instanceof Acme\Foo); // true
~~~

## Literal Arguments

Literal arguments do the opposite to resolvable ones, when the container encounters one of these, it just returns the associated value with no further resolution.

### What problem does this solve? 

It is mainly performance focused, however, the container will attempt possibly undesirable resolution on some types of argument.

### String

By default, the container will exhaust all possible options before returning a string.

- Is the string an alias of something else in the container?
- Is the string a class that needs to be instantiated?
- Is the string ultimately `callable`, and therefore should it be treated as a factory?

You can have the container treat a string as a literal string by defining that behaviour.

~~~ php
<?php 

declare(strict_types=1);

use League\Container\Argument\Literal;

$container = new League\Container\Container();
$container->add('alias1', new Literal\StringArgument('alias2'));
$container->add('alias2', Acme\Foo::class);

$foo = $container->get('alias1');

var_dump($foo instanceof Acme\Foo); // false
var_dump($foo === 'alias2'); // true
~~~

### Array

Similarly to a `string`, the container wants to determine if an `array`, You can avoid these checks by defining it an `array` as a literal.

~~~ php
<?php 

declare(strict_types=1);

use League\Container\Argument\Literal;

$container = new League\Container\Container();
$container->add('an-array', new Literal\ArrayArgument(['blah', 'blah2']));

$arr = $container->get('an-array');

var_dump($arr === ['blah', 'blah2']); // true
~~~

### Object and Callable

The container wants to treat any `callable` as a factory, so rather than returning your object, `Closure` etc, it will invoke it, and return the result of that.

Consider that you have an object that implements the magic `__invoke` method, but you don't want the container to actually invoke the `callable`, just return the object passed as an argument.

~~~php
<?php 

declare(strict_types=1);

namespace Acme;

class MyClass
{
    public function __invoke()
    {
        return 'hello';
    }
}
~~~

~~~ php
<?php 

declare(strict_types=1);

use League\Container\Argument\Literal;

$container = new League\Container\Container();
$container->add('object', new Acme\MyClass());
$container->add('literal-object', new Literal\ObjectArgument(new Acme\MyClass()); // Literal\CallableArgument could also be used here

$obj = $container->get('object');

var_dump($obj instanceof Acme\MyClass); // false
var_dump($obj === 'hello'); // true

$literalObj = $container->get('literal-object');

var_dump($literalObj instanceof Acme\MyClass); // true
var_dump($literalObj === 'hello'); // false
~~~

Similarly, if you want to pass any `callable` as an argument, the default behaviour will be to treat it as a factory and resolve it. You can avoid that by defining it as literal.

~~~ php
<?php 

declare(strict_types=1);

use League\Container\Argument\Literal;

$callback = function () {
    return 'hello';
};

$container = new League\Container\Container();
$container->add('callable', $callback);
$container->add('literal-callable', new Literal\CallableArgument($callback);

$cb = $container->get('callable');

var_dump($cb === $callback); // false
var_dump($cb === 'hello'); // true

$literalCb = $container->get('literal-callable');

var_dump($literalCb === $callback); // true
var_dump($literalCb === 'hello'); // false
~~~

### Boolean, Integer and Float

These have zero effect, and only exist for clarity and readability in your code, with a little type checking.

### All Literal Arguments

All literal argument classes are convenience sub-classes of `League\Container\Argument\LiteralArgument`.

- `League\Container\Argument\Literal\ArrayArgument`
- `League\Container\Argument\Literal\BooleanArgument`
- `League\Container\Argument\Literal\CallableArgument`
- `League\Container\Argument\Literal\FloatArgument`
- `League\Container\Argument\Literal\IntegerArgument`
- `League\Container\Argument\Literal\ObjectArgument`
- `League\Container\Argument\Literal\StringArgument`
