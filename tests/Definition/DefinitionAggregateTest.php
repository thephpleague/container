<?php

declare(strict_types=1);

namespace League\Container\Test\Definition;

use League\Container\Container;
use League\Container\Definition\{Definition, DefinitionAggregate, DefinitionInterface};
use League\Container\Exception\NotFoundException;
use League\Container\Test\Asset\Foo;
use PHPUnit\Framework\TestCase;

class DefinitionAggregateTest extends TestCase
{
    public function testAggregateAddsDefinition(): void
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $definition = $this->getMockBuilder(DefinitionInterface::class)->getMock();

        $definition
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias'))
            ->will(self::returnSelf())
        ;

        $aggregate  = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', $definition);

        self::assertInstanceOf(DefinitionInterface::class, $definition);
    }

    public function testAggregateCreatesDefinition(): void
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $aggregate  = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', Foo::class);
        self::assertSame('alias', $definition->getAlias());
    }

    public function testAggregateHasDefinition(): void
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $aggregate  = (new DefinitionAggregate())->setContainer($container);
        $aggregate->add('alias', Foo::class);
        self::assertTrue($aggregate->has('alias'));
        self::assertFalse($aggregate->has('nope'));
    }

    public function testAggregateAddsAndIteratesMultipleDefinitions(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new DefinitionAggregate())->setContainer($container);

        $definitions = [];

        for ($i = 0; $i < 10; $i++) {
            $definitions[] = $aggregate->add('alias' . $i, Foo::class);
        }

        foreach ($aggregate->getIterator() as $key => $definition) {
            self::assertSame($definitions[$key], $definition);
        }
    }

    public function testAggregateIteratesAndResolvesDefinition(): void
    {
        $aggregate   = new DefinitionAggregate();
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects(self::once())
            ->method('getAlias')
            ->willReturn('alias1')
        ;

        $definition1
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias1'))
            ->will(self::returnSelf())
        ;

        $definition2
            ->expects(self::once())
            ->method('getAlias')
            ->willReturn('alias2')
        ;

        $definition2
            ->expects(self::once())
            ->method('setContainer')
            ->with(self::equalTo($container))
            ->will(self::returnSelf())
        ;

        $definition2
            ->expects(self::once())
            ->method('setShared')
            ->with(self::equalTo(true))
            ->will(self::returnSelf())
        ;

        $definition2
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias2'))
            ->will(self::returnSelf())
        ;

        $definition2
            ->expects(self::once())
            ->method('resolve')
            ->will(self::returnSelf())
        ;

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->addShared('alias2', $definition2);

        $resolved = $aggregate->resolve('alias2');
        self::assertSame($definition2, $resolved);
    }

    public function testAggregateCanResolveArrayOfTaggedDefinitions(): void
    {
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects(self::once())
            ->method('setContainer')
            ->with(self::equalTo($container))
            ->will(self::returnSelf())
        ;

        $definition1
            ->expects(self::exactly(2))
            ->method('hasTag')
            ->with(self::equalTo('tag'))
            ->willReturn(true)
        ;

        $definition1
            ->expects(self::once())
            ->method('resolve')
            ->willReturn('definition1')
        ;

        $definition2
            ->expects(self::once())
            ->method('setContainer')
            ->with(self::equalTo($container))
            ->will(self::returnSelf())
        ;

        $definition2
            ->expects(self::once())
            ->method('hasTag')
            ->with(self::equalTo('tag'))
            ->willReturn(true)
        ;

        $definition2
            ->expects(self::once())
            ->method('resolve')
            ->willReturn('definition2')
        ;

        $aggregate = new DefinitionAggregate([$definition1, $definition2]);

        $aggregate->setContainer($container);
        self::assertTrue($aggregate->hasTag('tag'));
        $resolved = $aggregate->resolveTagged('tag');
        self::assertSame(['definition1', 'definition2'], $resolved);
    }

    public function testAggregateThrowsExceptionWhenCannotResolve(): void
    {
        $this->expectException(NotFoundException::class);

        $aggregate   = new DefinitionAggregate();
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects(self::once())
            ->method('getAlias')
            ->willReturn('alias1')
        ;

        $definition1
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias1'))
            ->will(self::returnSelf())
        ;

        $definition2
            ->expects(self::once())
            ->method('getAlias')
            ->willReturn('alias2')
        ;

        $definition2
            ->expects(self::once())
            ->method('setShared')
            ->with(self::equalTo(true))
            ->will(self::returnSelf())
        ;

        $definition2
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias2'))
            ->will(self::returnSelf())
        ;

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->addShared('alias2', $definition2);

        $aggregate->resolveNew('alias');
    }

    public function testDefinitionPreceedingSlash(): void
    {
        $container   = $this->getMockBuilder(Container::class)->getMock();
        $aggregate   = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = "\\League\\Container\\Test\\Asset\\Foo";
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition(Foo::class);

        self::assertInstanceOf(Definition::class, $definition);
    }

    public function testGetPreceedingSlash(): void
    {
        $container   = $this->getMockBuilder(Container::class)->getMock();
        $aggregate   = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = Foo::class;
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition("\\League\\Container\\Test\\Asset\\Foo");

        self::assertInstanceOf(Definition::class, $definition);
    }
}
