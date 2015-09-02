<?php

namespace League\Container\Test;

use League\Container\Container;
use League\Container\ImmutableContainerInterface;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the container can set and get a simple closure with args.
     */
    public function testSetsAndGetsSimplePrototypedClosure()
    {
        $container = new Container;

        $container->add('test', function ($arg) {
            return $arg;
        });

        $this->assertTrue($container->has('test'));

        $this->assertEquals($container->get('test', ['hello']), 'hello');
        $this->assertEquals($container->get('test', ['world']), 'world');
    }

    /**
     * Asserts that the container sets and gets an instance as shared.
     */
    public function testSetsAndGetInstanceAsShared()
    {
        $container = new Container;

        $class = new \stdClass;

        $container->add('test', $class);

        $this->assertTrue($container->has('test'));

        $this->assertSame($container->get('test'), $class);
    }

    /**
     * Asserts that the container sets and gets via a service provider.
     */
    public function testSetsAndGetsViaServiceProvider()
    {
        $container = new Container;

        $container->addServiceProvider(new Asset\ServiceProviderFake);

        $this->assertTrue($container->has('SomeService'));

        $this->assertEquals($container->get('SomeService', ['hello']), 'hello');
    }

    /**
     * Asserts that an exception is thrown when attempting to get service that
     * does not exist.
     */
    public function testThrowsWhenGettingUnmanagedService()
    {
        $this->setExpectedException('InvalidArgumentException');

        $container = new Container;

        $container->get('nothing');
    }

    /**
     * Asserts that container iterates over stacked containers to determine if alias is registered in one of them.
     */
    public function testHasChecksWithStack()
    {
        $alias = 'foo';

        $container = new Container;

        $container->delegate($this->getImmutableContainerMock());
        $container->delegate($this->getImmutableContainerMock([
            $alias => 'bar',
        ]));

        $this->assertTrue($container->has($alias));
    }

    /**
     * Asserts that container iterates over stacked containers to fetch item from stack.
     */
    public function testGetReturnsFromStack()
    {
        $alias = 'foo';
        $item = 'bar';

        $container = new Container;

        $container->delegate($this->getImmutableContainerMock());
        $container->delegate($this->getImmutableContainerMock([
            $alias => $item
        ]));

        $this->assertSame($item, $container->get($alias));
    }

    /**
     * Asserts that fetching a shared item always returns the same item.
     */
    public function testGetSharedItemReturnsTheSameItem()
    {
        $alias = 'foo';

        $container = new Container;

        $container->share($alias, function () {
            return new \stdClass;
        });

        $item = $container->get($alias);

        $this->assertSame($item, $container->get($alias));
    }

    /**
     * Asserts that asking container for an item that has a shared definition returns true.
     */
    public function testHasReturnsTrueForSharedDefinition()
    {
        $alias = 'foo';

        $container = new Container;

        $container->share($alias, function () {
            return new \stdClass;
        });

        $this->assertTrue($container->has($alias));
    }

    /**
     * @param array $items
     * @return \PHPUnit_Framework_MockObject_MockObject|ImmutableContainerInterface
     */
    private function getImmutableContainerMock(array $items = [])
    {
        $container = $this->getMockBuilder('League\Container\ImmutableContainerInterface')->getMock();

        $container
            ->expects($this->any())
            ->method('has')
            ->willReturnCallback(function ($alias) use ($items) {
                return array_key_exists($alias, $items);
            })
        ;

        $container
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($alias) use ($items) {
                if (array_key_exists($alias, $items)) {
                    return $items[$alias];
                }
            })
        ;

        return $container;
    }
}
