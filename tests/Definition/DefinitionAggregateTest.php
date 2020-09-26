<?php

declare(strict_types=1);

namespace League\Container\Test\Definition;

use League\Container\Exception\NotFoundException;
use League\Container\Definition\{DefinitionAggregate, DefinitionInterface};
use League\Container\Test\Asset\Foo;
use PHPUnit\Framework\TestCase;
use League\Container\Container;

class DefinitionAggregateTest extends TestCase
{
    /**
     * Asserts that the aggregate can add a definition.
     */
    public function testAggregateAddsDefinition(): void
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $definition = $this->getMockBuilder(DefinitionInterface::class)->getMock();

        $definition
            ->expects(self::once())
            ->method('setShared')
            ->with(self::equalTo(false))
            ->will(self::returnSelf())
        ;

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

    /**
     * Asserts that the aggregate can create a definition.
     */
    public function testAggregateCreatesDefinition(): void
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $aggregate  = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', Foo::class);

        self::assertSame('alias', $definition->getAlias());
    }

    /**
     * Asserts that the aggregate has a definition.
     */
    public function testAggregateHasDefinition(): void
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $aggregate  = (new DefinitionAggregate())->setContainer($container);
        $aggregate->add('alias', Foo::class);

        self::assertTrue($aggregate->has('alias'));
        self::assertFalse($aggregate->has('nope'));
    }

    /**
     * Asserts that the aggregate adds and iterates multiple definitions.
     */
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

    /**
     * Asserts that the aggregate iterates and resolves a definition.
     */
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
            ->method('setShared')
            ->with(self::equalTo(false))
            ->will(self::returnSelf())
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
            ->with(self::equalTo(false))
            ->will(self::returnSelf())
        ;

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->add('alias2', $definition2, true);

        $resolved = $aggregate->resolve('alias2');
        self::assertSame($definition2, $resolved);
    }

    /**
     * Asserts that the aggregate can resolved array of tagged definitions.
     */
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
            ->with(self::equalTo(false))
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
            ->with(self::equalTo(false))
            ->willReturn('definition2')
        ;

        $aggregate = new DefinitionAggregate([$definition1, $definition2]);

        $aggregate->setContainer($container);
        self::assertTrue($aggregate->hasTag('tag'));

        $resolved = $aggregate->resolveTagged('tag');
        self::assertSame(['definition1', 'definition2'], $resolved);
    }

    /**
     * Asserts that the aggregate throws an exception when a definition cannot be resolved.
     */
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
            ->method('setShared')
            ->with(self::equalTo(false))
            ->will(self::returnSelf())
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
        $aggregate->add('alias2', $definition2, true);

        $aggregate->resolve('alias');
    }
}
