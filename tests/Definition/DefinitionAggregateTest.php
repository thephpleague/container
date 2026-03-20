<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\Definition\Definition;
use League\Container\Definition\DefinitionAggregate;
use League\Container\Definition\DefinitionInterface;
use League\Container\Exception\NotFoundException;
use League\Container\Test\Asset\Foo;

test('aggregate adds definition', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $definition = $this->getMockBuilder(DefinitionInterface::class)->getMock();

    $definition
        ->expects($this->once())
        ->method('setAlias')
        ->with($this->equalTo('alias'))
        ->willReturnSelf();

    /** @var DefinitionAggregate $aggregate */
    $aggregate = (new DefinitionAggregate())->setContainer($container);
    $definition = $aggregate->add('alias', $definition);

    expect($definition)->toBeInstanceOf(DefinitionInterface::class);
});

test('aggregate creates definition', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    /** @var DefinitionAggregate $aggregate */
    $aggregate = (new DefinitionAggregate())->setContainer($container);
    $definition = $aggregate->add('alias', Foo::class);

    expect($definition->getAlias())->toBe('alias');
});

test('aggregate has definition', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    /** @var DefinitionAggregate $aggregate */
    $aggregate = (new DefinitionAggregate())->setContainer($container);
    $aggregate->add('alias', Foo::class);

    expect($aggregate->has('alias'))->toBeTrue();
    expect($aggregate->has('nope'))->toBeFalse();
});

test('aggregate adds and iterates multiple definitions', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    /** @var DefinitionAggregate $aggregate */
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
    $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
    $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
    $container = $this->getMockBuilder(Container::class)->getMock();

    $definition1
        ->expects($this->once())
        ->method('getAlias')
        ->willReturn('alias1');

    $definition1
        ->expects($this->once())
        ->method('setAlias')
        ->with($this->equalTo('alias1'))
        ->willReturnSelf();

    $definition2
        ->expects($this->once())
        ->method('getAlias')
        ->willReturn('alias2');

    $definition2
        ->expects($this->once())
        ->method('setContainer')
        ->with($this->equalTo($container))
        ->willReturnSelf();

    $definition2
        ->expects($this->once())
        ->method('setShared')
        ->with($this->equalTo(true))
        ->willReturnSelf();

    $definition2
        ->expects($this->once())
        ->method('setAlias')
        ->with($this->equalTo('alias2'))
        ->willReturnSelf();

    $definition2
        ->expects($this->once())
        ->method('resolve')
        ->willReturnSelf();

    $aggregate->setContainer($container);

    $aggregate->add('alias1', $definition1);
    $aggregate->addShared('alias2', $definition2);

    $resolved = $aggregate->resolve('alias2');

    expect($resolved)->toBe($definition2);
});

test('aggregate can resolve array of tagged definitions', function () {
    $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
    $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
    $container = $this->getMockBuilder(Container::class)->getMock();

    $definition1
        ->expects($this->once())
        ->method('setContainer')
        ->with($this->equalTo($container))
        ->willReturnSelf();

    $definition1
        ->expects($this->exactly(2))
        ->method('hasTag')
        ->with($this->equalTo('tag'))
        ->willReturn(true);

    $definition1
        ->expects($this->once())
        ->method('resolve')
        ->willReturn('definition1');

    $definition2
        ->expects($this->once())
        ->method('setContainer')
        ->with($this->equalTo($container))
        ->willReturnSelf();

    $definition2
        ->expects($this->once())
        ->method('hasTag')
        ->with($this->equalTo('tag'))
        ->willReturn(true);

    $definition2
        ->expects($this->once())
        ->method('resolve')
        ->willReturn('definition2');

    $aggregate = new DefinitionAggregate([$definition1, $definition2]);

    $aggregate->setContainer($container);

    expect($aggregate->hasTag('tag'))->toBeTrue();
    expect($aggregate->resolveTagged('tag'))->toBe(['definition1', 'definition2']);
});

test('aggregate throws exception when cannot resolve', function () {
    $aggregate = new DefinitionAggregate();
    $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
    $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
    $container = $this->getMockBuilder(Container::class)->getMock();

    $definition1
        ->expects($this->once())
        ->method('getAlias')
        ->willReturn('alias1');

    $definition1
        ->expects($this->once())
        ->method('setAlias')
        ->with($this->equalTo('alias1'))
        ->willReturnSelf();

    $definition2
        ->expects($this->once())
        ->method('getAlias')
        ->willReturn('alias2');

    $definition2
        ->expects($this->once())
        ->method('setShared')
        ->with($this->equalTo(true))
        ->willReturnSelf();

    $definition2
        ->expects($this->once())
        ->method('setAlias')
        ->with($this->equalTo('alias2'))
        ->willReturnSelf();

    $aggregate->setContainer($container);

    $aggregate->add('alias1', $definition1);
    $aggregate->addShared('alias2', $definition2);

    expect(fn () => $aggregate->resolveNew('alias'))->toThrow(NotFoundException::class);
});

test('definition preceding slash', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new DefinitionAggregate();
    $aggregate->setContainer($container);

    $someClass = "\\League\\Container\\Test\\Asset\\Foo";
    $aggregate->add($someClass, null);

    $definition = $aggregate->getDefinition(Foo::class);

    expect($definition)->toBeInstanceOf(Definition::class);
});

test('get preceding slash', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new DefinitionAggregate();
    $aggregate->setContainer($container);

    $someClass = Foo::class;
    $aggregate->add($someClass, null);

    $definition = $aggregate->getDefinition("\\League\\Container\\Test\\Asset\\Foo");

    expect($definition)->toBeInstanceOf(Definition::class);
});

test('definition preceding slash singular quotes', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new DefinitionAggregate();
    $aggregate->setContainer($container);

    $someClass = '\\League\\Container\\Test\\Asset\\Foo';
    $aggregate->add($someClass, null);

    $definition = $aggregate->getDefinition(Foo::class);

    expect($definition)->toBeInstanceOf(Definition::class);
});

test('get preceding slash singular quote', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new DefinitionAggregate();
    $aggregate->setContainer($container);

    $someClass = Foo::class;
    $aggregate->add($someClass, null);

    $definition = $aggregate->getDefinition('\\League\\Container\\Test\\Asset\\Foo');

    expect($definition)->toBeInstanceOf(Definition::class);
});
