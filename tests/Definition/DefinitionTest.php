<?php

declare(strict_types=1);

use League\Container\Argument\Literal;
use League\Container\Argument\ResolvableArgument;
use League\Container\Container;
use League\Container\Definition\Definition;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\BarInterface;
use League\Container\Test\Asset\Foo;
use League\Container\Test\Asset\FooCallable;
use League\Container\Test\Asset\FooWithRequiredDependency;
use League\Container\Exception\ContainerException;

test('definition resolves closure with defined args', function () {
    $definition = new Definition('callable', function (...$args) {
        return implode(' ', $args);
    });

    $definition->addArguments(['hello', 'world']);

    expect($definition->resolve())->toBe('hello world');
});

test('definition resolves closure returning raw argument', function () {
    $definition = new Definition('callable', function () {
        return new Literal\StringArgument('hello world');
    });

    expect($definition->resolve())->toBe('hello world');
});

test('definition resolves callable class', function () {
    $definition = new Definition('callable', new FooCallable());
    $definition->addArgument(new Bar());

    expect($definition->resolve())->toBeInstanceOf(Foo::class);
});

test('definition resolves array callable', function () {
    $definition = new Definition('callable', [new FooCallable(), '__invoke']);
    $definition->addArgument(new Bar());

    expect($definition->resolve())->toBeInstanceOf(Foo::class);
});

test('definition resolves class with method calls', function () {
    $container = Mockery::mock(Container::class);
    $bar = new Bar();

    $container->allows('has')->andReturnUsing(fn(string $id) => match ($id) {
        Foo::class => false,
        Bar::class => true,
    });
    $container->shouldReceive('get')->once()->with(Bar::class)->andReturn($bar);

    $definition = new Definition('callable', Foo::class);
    $definition->setContainer($container);
    $definition->addMethodCalls(['setBar' => [Bar::class]]);

    $actual = $definition->resolve();

    expect($actual)->toBeInstanceOf(Foo::class);
    expect($actual->bar)->toBeInstanceOf(Bar::class);
});

test('definition resolves class with defined args', function () {
    $container = Mockery::mock(Container::class);
    $bar = new Bar();

    $container->allows('has')->andReturnUsing(fn(string $id) => match ($id) {
        Foo::class => false,
        Bar::class => true,
    });
    $container->shouldReceive('get')->once()->with(Bar::class)->andReturn($bar);

    $definition = new Definition('callable', Foo::class);
    $definition->setContainer($container);
    $definition->addArgument(Bar::class);

    $actual = $definition->resolve();

    expect($actual)->toBeInstanceOf(Foo::class);
    expect($actual->bar)->toBeInstanceOf(Bar::class);
});

test('definition resolves shared item only once', function () {
    $definition = new Definition('class', Foo::class);
    $definition->setShared();

    $actual1 = $definition->resolve();
    $actual2 = $definition->resolve();
    $actual3 = $definition->resolveNew();

    expect($actual2)->toBe($actual1);
    expect($actual3)->not->toBe($actual1);
});

test('definition resolves nested alias', function () {
    $aliasDefinition = new Definition('alias', new ResolvableArgument('class'));
    $definition = new Definition('class', Foo::class);
    $container = Mockery::mock(Container::class);

    $expected = $definition->resolve();

    $container->shouldReceive('has')->once()->with('class')->andReturn(true);
    $container->shouldReceive('get')->once()->with('class')->andReturn($expected);

    $aliasDefinition->setContainer($container);

    expect($aliasDefinition->resolve())->toBe($expected);
});

test('definition can add tags', function () {
    $definition = new Definition('class', Foo::class);
    $definition->addTag('tag1')->addTag('tag2');

    expect($definition->hasTag('tag1'))->toBeTrue();
    expect($definition->hasTag('tag2'))->toBeTrue();
    expect($definition->hasTag('tag3'))->toBeFalse();
});

test('definition can get concrete', function () {
    $concrete = new Literal\StringArgument(Foo::class);
    $definition = new Definition('class', $concrete);

    expect($definition->getConcrete())->toBe($concrete);
});

test('definition can set concrete', function () {
    $definition = new Definition('class', null);
    $concrete = new Literal\StringArgument(Foo::class);
    $definition->setConcrete($concrete);

    expect($definition->getConcrete())->toBe($concrete);
});

test('non existent class is returned as identical string', function () {
    $nonExistent = 'NonExistent';
    $definition = new Definition($nonExistent);

    expect($definition->getAlias())->toBe($nonExistent);
    expect($definition->resolve())->toBe($nonExistent);
});

test('definition delegates to container for different concrete', function () {
    $container = Mockery::mock(Container::class);
    $bar = new Bar();

    $container->shouldReceive('has')->once()->with(Bar::class)->andReturn(true);
    $container->shouldReceive('get')->once()->with(Bar::class)->andReturn($bar);

    $definition = new Definition(BarInterface::class, Bar::class);
    $definition->setContainer($container);

    $actual = $definition->resolveNew();

    expect($actual)->toBeInstanceOf(Bar::class);
    expect($actual)->toBe($bar);
});

test('definition resolves own class when concrete matches id', function () {
    $container = Mockery::mock(Container::class);

    $container->shouldNotReceive('has');
    $container->shouldNotReceive('get');

    $definition = new Definition(Foo::class, Foo::class);
    $definition->setContainer($container);

    expect($definition->resolveNew())->toBeInstanceOf(Foo::class);
});

test('definition delegates to container when concrete comes from resolvable argument', function () {
    $container = Mockery::mock(Container::class);
    $bar = new Bar();

    $container->shouldReceive('has')->once()->with(Bar::class)->andReturn(true);
    $container->shouldReceive('get')->once()->with(Bar::class)->andReturn($bar);

    $definition = new Definition(BarInterface::class, new ResolvableArgument(Bar::class));
    $definition->setContainer($container);

    $actual = $definition->resolveNew();

    expect($actual)->toBeInstanceOf(Bar::class);
    expect($actual)->toBe($bar);
});

test('resolve class throws container exception for unsatisfied dependencies', function () {
    $definition = new Definition(FooWithRequiredDependency::class);

    expect(fn() => $definition->resolveNew())
        ->toThrow(ContainerException::class, 'unsatisfied dependencies');
});
