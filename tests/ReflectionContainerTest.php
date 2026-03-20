<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;
use League\Container\Test\Asset\FooCallable;
use League\Container\Test\Asset\FooWithAttr;
use League\Container\Test\Asset\ProBar;
use League\Container\Test\Asset\ProFoo;

function getContainerMock(PHPUnit\Framework\TestCase $testCase, array $items = []): Container
{
    $container = $testCase->getMockBuilder(Container::class)->getMock();

    $container
        ->method('has')
        ->willReturnCallback(function ($alias) use ($items) {
            return array_key_exists($alias, $items);
        });

    $container
        ->method('get')
        ->willReturnCallback(function ($alias) use ($items) {
            if (array_key_exists($alias, $items)) {
                return $items[$alias];
            }

            return null;
        });

    return $container;
}

test('has returns true if class exists', function () {
    $container = new ReflectionContainer();

    expect($container->has(ReflectionContainer::class))->toBeTrue();
});

test('has returns false if class does not exist', function () {
    $container = new ReflectionContainer();

    expect($container->has('blah'))->toBeFalse();
});

test('container instantiates class without constructor', function () {
    $container = new ReflectionContainer();

    expect($container->get(stdClass::class))->toBeInstanceOf(stdClass::class);
});

test('container instantiates and caches class without constructor', function () {
    $container = new ReflectionContainer(true);

    $instanceOne = $container->get(stdClass::class);
    $instanceTwo = $container->get(stdClass::class);

    expect($instanceOne)->toBeInstanceOf(stdClass::class);
    expect($instanceTwo)->toBeInstanceOf(stdClass::class);
    expect($instanceTwo)->toBe($instanceOne);
});

test('get instantiates class with constructor', function () {
    $container = new ReflectionContainer();
    $item = $container->get(Foo::class);

    expect($item)->toBeInstanceOf(Foo::class);
    expect($item->bar)->toBeInstanceOf(Bar::class);
});

test('get instantiates and caches class with constructor', function () {
    $container = new ReflectionContainer(true);

    $itemOne = $container->get(Foo::class);
    $itemTwo = $container->get(Foo::class);

    expect($itemOne)->toBeInstanceOf(Foo::class);
    expect($itemOne->bar)->toBeInstanceOf(Bar::class);
    expect($itemTwo)->toBeInstanceOf(Foo::class);
    expect($itemTwo->bar)->toBeInstanceOf(Bar::class);
    expect($itemTwo)->toBe($itemOne);
    expect($itemTwo->bar)->toBe($itemOne->bar);
});

test('get instantiates class with constructor and uses container', function () {
    $dependency = new Bar();
    $container = new ReflectionContainer();

    $container->setContainer(getContainerMock($this, [
        Bar::class => $dependency,
    ]));

    $item = $container->get(Foo::class);

    expect($item)->toBeInstanceOf(Foo::class);
    expect($item->bar)->toBe($dependency);
});

test('get instantiates class with constructor and uses arguments', function () {
    $dependency = new Bar();
    $container = new ReflectionContainer();

    $item = $container->get(Foo::class, [
        'bar' => $dependency,
    ]);

    expect($item)->toBeInstanceOf(Foo::class);
    expect($item->bar)->toBe($dependency);
});

test('throws when getting non existent class', function () {
    $container = new ReflectionContainer();

    expect(fn () => $container->get('Whoooo'))->toThrow(NotFoundException::class);
});

test('call reflects on closure arguments', function () {
    $container = new ReflectionContainer();

    $foo = $container->call(function (Foo $foo) {
        return $foo;
    });

    expect($foo)->toBeInstanceOf(Foo::class);
    expect($foo->bar)->toBeInstanceOf(Bar::class);
});

test('call reflects on instance method arguments', function () {
    $container = new ReflectionContainer();
    $foo = new Foo();
    $container->call([$foo, 'setBar']);

    expect($foo)->toBeInstanceOf(Foo::class);
    expect($foo->bar)->toBeInstanceOf(Bar::class);
});

test('call reflects on static method arguments', function () {
    $container = new ReflectionContainer();
    $container->call('League\Container\Test\Asset\Foo::staticSetBar');

    expect(Foo::$staticBar)->toBeInstanceOf(Bar::class);
    expect(Foo::$staticHello)->toEqual('hello world');
});

test('call throws when argument cannot be resolved', function () {
    $container = new ReflectionContainer();

    expect(fn () => $container->call([new Bar(), 'setSomething']))->toThrow(NotFoundException::class);
});

test('call resolves invokable class', function () {
    $container = new ReflectionContainer();
    $foo = $container->call(new FooCallable(), [new Bar()]);

    expect($foo)->toBeInstanceOf(Foo::class);
    expect($foo->bar)->toBeInstanceOf(Bar::class);
});

test('call resolves function', function () {
    $container = new ReflectionContainer();
    $foo = $container->call('League\Container\Test\Asset\test', [new Bar()]);

    expect($foo)->toBeInstanceOf(Foo::class);
    expect($foo->bar)->toBeInstanceOf(Bar::class);
});

test('get instantiates class with constructor and skips protected constructor', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());

    $item = $container->get(ProFoo::class);

    expect($item)->toBeInstanceOf(ProFoo::class);
    expect($item->bar)->toBeNull();
});

test('get instantiates class with constructor and uses factory', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(ProBar::class, [ProBar::class, 'factory']);

    $item = $container->get(ProFoo::class);

    expect($item)->toBeInstanceOf(ProFoo::class);
    expect($item->bar)->toBeInstanceOf(ProBar::class);
});

test('get instantiates class with constructor and attributes', function () {
    $container = new Container();
    $reflectionContainer = new ReflectionContainer();
    $reflectionContainer->setMode(ReflectionContainer::ATTRIBUTE_RESOLUTION);
    $container->delegate($reflectionContainer);

    $item = $container->get(FooWithAttr::class);

    expect($item)->toBeInstanceOf(FooWithAttr::class);
    expect($item->bar)->toBeInstanceOf(Bar::class);
});
