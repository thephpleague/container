<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\Definition\Definition;
use League\Container\Definition\DefinitionAggregate;
use League\Container\Definition\DefinitionInterface;
use League\Container\Exception\NotFoundException;
use League\Container\Test\Asset\Foo;

test('aggregate adds definition', function () {
    $container = Mockery::mock(Container::class);
    $definition = Mockery::mock(DefinitionInterface::class);

    $definition
        ->shouldReceive('setAlias')
        ->once()
        ->with('alias')
        ->andReturnSelf();

    $aggregate = (new DefinitionAggregate())->setContainer($container);
    $definition = $aggregate->add('alias', $definition);

    expect($definition)->toBeInstanceOf(DefinitionInterface::class);
});

test('aggregate creates definition', function () {
    $container = Mockery::mock(Container::class);
    $aggregate = (new DefinitionAggregate())->setContainer($container);
    $definition = $aggregate->add('alias', Foo::class);

    expect($definition->getAlias())->toBe('alias');
});

test('aggregate has definition', function () {
    $container = Mockery::mock(Container::class);
    $aggregate = (new DefinitionAggregate())->setContainer($container);
    $aggregate->add('alias', Foo::class);

    expect($aggregate->has('alias'))->toBeTrue();
    expect($aggregate->has('nope'))->toBeFalse();
});

test('aggregate adds and iterates multiple definitions', function () {
    $container = Mockery::mock(Container::class);
    $aggregate = (new DefinitionAggregate())->setContainer($container);

    $definitions = [];

    for ($i = 0; $i < 10; $i++) {
        $definitions[] = $aggregate->add('alias' . $i, Foo::class);
    }

    foreach ($aggregate->getIterator() as $key => $definition) {
        expect($definition)->toBe($definitions[$key]);
    }
});

test('aggregate iterates and resolves definition', function () {
    $aggregate = new DefinitionAggregate();
    $definition1 = Mockery::mock(DefinitionInterface::class);
    $definition2 = Mockery::mock(DefinitionInterface::class);
    $container = Mockery::mock(Container::class);

    $definition1->shouldReceive('getAlias')->andReturn('alias1');
    $definition1->shouldReceive('setAlias')->once()->with('alias1')->andReturnSelf();

    $definition2->shouldReceive('getAlias')->andReturn('alias2');
    $definition2->shouldReceive('setContainer')->once()->with($container)->andReturnSelf();
    $definition2->shouldReceive('setShared')->once()->with(true)->andReturnSelf();
    $definition2->shouldReceive('setAlias')->once()->with('alias2')->andReturnSelf();
    $definition2->shouldReceive('resolve')->once()->andReturnSelf();

    $aggregate->setContainer($container);

    $aggregate->add('alias1', $definition1);
    $aggregate->addShared('alias2', $definition2);

    $resolved = $aggregate->resolve('alias2');

    expect($resolved)->toBe($definition2);
});

test('aggregate can resolve array of tagged definitions', function () {
    $definition1 = Mockery::mock(DefinitionInterface::class);
    $definition2 = Mockery::mock(DefinitionInterface::class);
    $container = Mockery::mock(Container::class);

    $definition1->shouldReceive('setContainer')->once()->with($container)->andReturnSelf();
    $definition1->shouldReceive('hasTag')->with('tag')->twice()->andReturn(true);
    $definition1->shouldReceive('resolve')->once()->andReturn('definition1');

    $definition2->shouldReceive('setContainer')->once()->with($container)->andReturnSelf();
    $definition2->shouldReceive('hasTag')->with('tag')->once()->andReturn(true);
    $definition2->shouldReceive('resolve')->once()->andReturn('definition2');

    $aggregate = new DefinitionAggregate([$definition1, $definition2]);

    $aggregate->setContainer($container);

    expect($aggregate->hasTag('tag'))->toBeTrue();
    expect($aggregate->resolveTagged('tag'))->toBe(['definition1', 'definition2']);
});

test('aggregate throws exception when cannot resolve', function () {
    $aggregate = new DefinitionAggregate();
    $definition1 = Mockery::mock(DefinitionInterface::class);
    $definition2 = Mockery::mock(DefinitionInterface::class);
    $container = Mockery::mock(Container::class);

    $definition1->shouldReceive('getAlias')->andReturn('alias1');
    $definition1->shouldReceive('setAlias')->once()->with('alias1')->andReturnSelf();

    $definition2->shouldReceive('getAlias')->andReturn('alias2');
    $definition2->shouldReceive('setShared')->once()->with(true)->andReturnSelf();
    $definition2->shouldReceive('setAlias')->once()->with('alias2')->andReturnSelf();

    $aggregate->setContainer($container);

    $aggregate->add('alias1', $definition1);
    $aggregate->addShared('alias2', $definition2);

    expect(fn() => $aggregate->resolveNew('alias'))->toThrow(NotFoundException::class);
});

test('definition preceding slash', function () {
    $container = Mockery::mock(Container::class);
    $aggregate = new DefinitionAggregate();
    $aggregate->setContainer($container);

    $someClass = '\\League\\Container\\Test\\Asset\\Foo';
    $aggregate->add($someClass, null);

    $definition = $aggregate->getDefinition(Foo::class);

    expect($definition)->toBeInstanceOf(Definition::class);
});

test('get preceding slash', function () {
    $container = Mockery::mock(Container::class);
    $aggregate = new DefinitionAggregate();
    $aggregate->setContainer($container);

    $someClass = Foo::class;
    $aggregate->add($someClass, null);

    $definition = $aggregate->getDefinition('\\League\\Container\\Test\\Asset\\Foo');

    expect($definition)->toBeInstanceOf(Definition::class);
});

test('definition preceding slash singular quotes', function () {
    $container = Mockery::mock(Container::class);
    $aggregate = new DefinitionAggregate();
    $aggregate->setContainer($container);

    $someClass = '\\League\\Container\\Test\\Asset\\Foo';
    $aggregate->add($someClass, null);

    $definition = $aggregate->getDefinition(Foo::class);

    expect($definition)->toBeInstanceOf(Definition::class);
});

test('get preceding slash singular quote', function () {
    $container = Mockery::mock(Container::class);
    $aggregate = new DefinitionAggregate();
    $aggregate->setContainer($container);

    $someClass = Foo::class;
    $aggregate->add($someClass, null);

    $definition = $aggregate->getDefinition('\\League\\Container\\Test\\Asset\\Foo');

    expect($definition)->toBeInstanceOf(Definition::class);
});
