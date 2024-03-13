<?php

declare(strict_types=1);

namespace League\Container\Test\Definition;

use League\Container\Container;
use League\Container\Definition\{DefinitionAggregate, DefinitionInterface};
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
            ->expects($this->once())
            ->method('setAlias')
            ->with($this->equalTo('alias'))
            ->will($this->returnSelf())
        ;

        $aggregate  = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', $definition);

        $this->assertInstanceOf(DefinitionInterface::class, $definition);
    }

    public function testAggregateCreatesDefinition(): void
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $aggregate  = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', Foo::class);
        $this->assertSame('alias', $definition->getAlias());
    }

    public function testAggregateHasDefinition(): void
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $aggregate  = (new DefinitionAggregate())->setContainer($container);
        $aggregate->add('alias', Foo::class);
        $this->assertTrue($aggregate->has('alias'));
        $this->assertFalse($aggregate->has('nope'));
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
            $this->assertSame($definitions[$key], $definition);
        }
    }

    public function testAggregateIteratesAndResolvesDefinition(): void
    {
        $aggregate   = new DefinitionAggregate();
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects($this->once())
            ->method('getAlias')
            ->willReturn('alias1')
        ;

        $definition1
            ->expects($this->once())
            ->method('setAlias')
            ->with($this->equalTo('alias1'))
            ->will($this->returnSelf())
        ;

        $definition2
            ->expects($this->once())
            ->method('getAlias')
            ->willReturn('alias2')
        ;

        $definition2
            ->expects($this->once())
            ->method('setContainer')
            ->with($this->equalTo($container))
            ->will($this->returnSelf())
        ;

        $definition2
            ->expects($this->once())
            ->method('setShared')
            ->with($this->equalTo(true))
            ->will($this->returnSelf())
        ;

        $definition2
            ->expects($this->once())
            ->method('setAlias')
            ->with($this->equalTo('alias2'))
            ->will($this->returnSelf())
        ;

        $definition2
            ->expects($this->once())
            ->method('resolve')
            ->will($this->returnSelf())
        ;

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->addShared('alias2', $definition2);

        $resolved = $aggregate->resolve('alias2');
        $this->assertSame($definition2, $resolved);
    }

    public function testAggregateCanResolveArrayOfTaggedDefinitions(): void
    {
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects($this->once())
            ->method('setContainer')
            ->with($this->equalTo($container))
            ->will($this->returnSelf())
        ;

        $definition1
            ->expects($this->exactly(2))
            ->method('hasTag')
            ->with($this->equalTo('tag'))
            ->willReturn(true)
        ;

        $definition1
            ->expects($this->once())
            ->method('resolve')
            ->willReturn('definition1')
        ;

        $definition2
            ->expects($this->once())
            ->method('setContainer')
            ->with($this->equalTo($container))
            ->will($this->returnSelf())
        ;

        $definition2
            ->expects($this->once())
            ->method('hasTag')
            ->with($this->equalTo('tag'))
            ->willReturn(true)
        ;

        $definition2
            ->expects($this->once())
            ->method('resolve')
            ->willReturn('definition2')
        ;

        $aggregate = new DefinitionAggregate([$definition1, $definition2]);

        $aggregate->setContainer($container);
        $this->assertTrue($aggregate->hasTag('tag'));
        $resolved = $aggregate->resolveTagged('tag');
        $this->assertSame(['definition1', 'definition2'], $resolved);
    }

    public function testAggregateThrowsExceptionWhenCannotResolve(): void
    {
        $this->expectException(NotFoundException::class);

        $aggregate   = new DefinitionAggregate();
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects($this->once())
            ->method('getAlias')
            ->willReturn('alias1')
        ;

        $definition1
            ->expects($this->once())
            ->method('setAlias')
            ->with($this->equalTo('alias1'))
            ->will($this->returnSelf())
        ;

        $definition2
            ->expects($this->once())
            ->method('getAlias')
            ->willReturn('alias2')
        ;

        $definition2
            ->expects($this->once())
            ->method('setShared')
            ->with($this->equalTo(true))
            ->will($this->returnSelf())
        ;

        $definition2
            ->expects($this->once())
            ->method('setAlias')
            ->with($this->equalTo('alias2'))
            ->will($this->returnSelf())
        ;

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->addShared('alias2', $definition2);

        $aggregate->resolveNew('alias');
    }
}
