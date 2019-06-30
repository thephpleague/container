<?php declare(strict_types=1);

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
    public function testAggregateAddsDefinition()
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $definition = $this->getMockBuilder(DefinitionInterface::class)->getMock();

        $definition->expects($this->once())->method('setShared')->with($this->equalTo(false))->will($this->returnSelf());
        $definition->expects($this->once())->method('setAlias')->with($this->equalTo('alias'))->will($this->returnSelf());

        $aggregate  = (new DefinitionAggregate)->setLeagueContainer($container);
        $definition = $aggregate->add('alias', $definition);

        $this->assertInstanceOf(DefinitionInterface::class, $definition);
    }

    /**
     * Asserts that the aggregate can create a definition.
     */
    public function testAggregateCreatesDefinition()
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $aggregate  = (new DefinitionAggregate)->setLeagueContainer($container);
        $definition = $aggregate->add('alias', Foo::class);

        $this->assertSame('alias', $definition->getAlias());
    }

    /**
     * Asserts that the aggregate has a definition.
     */
    public function testAggregateHasDefinition()
    {
        $container  = $this->getMockBuilder(Container::class)->getMock();
        $aggregate  = (new DefinitionAggregate)->setLeagueContainer($container);
        $aggregate->add('alias', Foo::class);

        $this->assertTrue($aggregate->has('alias'));
        $this->assertFalse($aggregate->has('nope'));
    }

    /**
     * Asserts that the aggregate adds and iterates multiple definitions.
     */
    public function testAggregateAddsAndIteratesMultipleDefinitions()
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new DefinitionAggregate)->setLeagueContainer($container);

        $definitions = [];

        for ($i = 0; $i < 10; $i++) {
            $definitions[] = $aggregate->add('alias' . $i, Foo::class);
        }

        foreach ($aggregate->getIterator() as $key => $definition) {
            $this->assertSame($definitions[$key], $definition);
        }
    }

    /**
     * Asserts that the aggregate iterates and resolves a definition.
     */
    public function testAggregateIteratesAndResolvesDefinition()
    {
        $aggregate   = new DefinitionAggregate;
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1->expects($this->once())->method('getAlias')->willReturn('alias1');
        $definition1->expects($this->once())->method('setShared')->with($this->equalTo(false))->will($this->returnSelf());
        $definition1->expects($this->once())->method('setAlias')->with($this->equalTo('alias1'))->will($this->returnSelf());

        $definition2->expects($this->once())->method('getAlias')->willReturn('alias2');
        $definition2->expects($this->once())->method('setLeagueContainer')->with($this->equalTo($container))->will($this->returnSelf());
        $definition2->expects($this->once())->method('setShared')->with($this->equalTo(true))->will($this->returnSelf());
        $definition2->expects($this->once())->method('setAlias')->with($this->equalTo('alias2'))->will($this->returnSelf());
        $definition2->expects($this->once())->method('resolve')->with($this->equalTo(false))->will($this->returnSelf());

        $aggregate->setLeagueContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->add('alias2', $definition2, true);

        $resolved = $aggregate->resolve('alias2');
        $this->assertSame($definition2, $resolved);
    }

    /**
     * Asserts that the aggregate can resolved array of tagged definitions.
     */
    public function testAggregateCanResolveArrayOfTaggedDefinitions()
    {
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1->expects($this->once())->method('setLeagueContainer')->with($this->equalTo($container))->will($this->returnSelf());
        $definition1->expects($this->exactly(2))->method('hasTag')->with($this->equalTo('tag'))->willReturn(true);
        $definition1->expects($this->once())->method('resolve')->with($this->equalTo(false))->willReturn('definition1');

        $definition2->expects($this->once())->method('setLeagueContainer')->with($this->equalTo($container))->will($this->returnSelf());
        $definition2->expects($this->once())->method('hasTag')->with($this->equalTo('tag'))->willReturn(true);
        $definition2->expects($this->once())->method('resolve')->with($this->equalTo(false))->willReturn('definition2');

        $aggregate = new DefinitionAggregate([$definition1, $definition2]);

        $aggregate->setLeagueContainer($container);

        $this->assertTrue($aggregate->hasTag('tag'));

        $resolved = $aggregate->resolveTagged('tag');
        $this->assertSame(['definition1', 'definition2'], $resolved);
    }

    /**
     * Asserts that the aggregate throws an exception when a definition cannot be resolved.
     */
    public function testAggregateThrowsExceptionWhenCannotResolve()
    {
        $this->expectException(NotFoundException::class);

        $aggregate   = new DefinitionAggregate;
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container   = $this->getMockBuilder(Container::class)->getMock();

        $definition1->expects($this->once())->method('getAlias')->willReturn('alias1');
        $definition1->expects($this->once())->method('setShared')->with($this->equalTo(false))->will($this->returnSelf());
        $definition1->expects($this->once())->method('setAlias')->with($this->equalTo('alias1'))->will($this->returnSelf());

        $definition2->expects($this->once())->method('getAlias')->willReturn('alias2');
        $definition2->expects($this->once())->method('setShared')->with($this->equalTo(true))->will($this->returnSelf());
        $definition2->expects($this->once())->method('setAlias')->with($this->equalTo('alias2'))->will($this->returnSelf());

        $aggregate->setLeagueContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->add('alias2', $definition2, true);

        $aggregate->resolve('alias');
    }
}
