<?php

declare(strict_types=1);

use League\Container\Attribute\Inject;
use League\Container\Container;
use League\Container\Test\Asset\Foo;

test('can instantiate with id', function () {
    $container = Mockery::mock(Container::class);
    $container->allows('get')->with(Foo::class)->andReturn(new Foo());

    $inject = new Inject(Foo::class);
    $inject->setContainer($container);

    expect($inject)->toBeInstanceOf(Inject::class);
    expect($inject)->toHaveProperty('id');
    expect($inject->resolve())->toBeInstanceOf(Foo::class);
});
