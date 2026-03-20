<?php

declare(strict_types=1);

use League\Container\Attribute\Resolve;
use League\Container\Container;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;

test('can instantiate with class resolver and single segment path', function () {
    $foo = new Foo();
    $foo->setBar(new Bar());

    $container = $this->createMock(Container::class);
    $container->method('get')->with(Foo::class)->willReturn($foo);

    $resolve = new Resolve(Foo::class, 'bar');
    $resolve->setContainer($container);

    expect($resolve)->toBeInstanceOf(Resolve::class);
    expect($resolve)->toHaveProperty('resolver');
    expect($resolve)->toHaveProperty('path');
    expect($resolve->resolve())->toBeInstanceOf(Bar::class);
});

test('can instantiate with class resolver and multi segment path', function () {
    $foo = new Foo();
    $bar = new Bar();
    $bar->setSomething(['foo' => 'bar']);
    $foo->setBar($bar);

    $container = $this->createMock(Container::class);
    $container->method('get')->with(Foo::class)->willReturn($foo);

    $resolve = new Resolve(Foo::class, 'bar.getSomething.foo');
    $resolve->setContainer($container);

    expect($resolve)->toBeInstanceOf(Resolve::class);
    expect($resolve)->toHaveProperty('resolver');
    expect($resolve)->toHaveProperty('path');
    expect($resolve->resolve())->toEqual('bar');
});
