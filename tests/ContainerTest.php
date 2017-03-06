<?php

namespace League\Container\Test;

use League\Container\Argument\RawArgument;
use League\Container\Container;
use League\Container\ImmutableContainerInterface;
use League\Container\ReflectionContainer;

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
        $this->assertEquals($container->get('test', [true]), true);
        $this->assertEquals($container->get('test', [false]), false);
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
     * Asserts that the container can set and get a concrete value wrapped in a RawArgument.
     */
    public function testSetsAndGetsRawArgument()
    {
        $container = new Container;

        $container->add('test', new RawArgument('some_string'));

        $this->assertTrue($container->has('test'));

        $this->assertEquals('some_string', $container->get('test'));
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
     * Asserts that a shared service provided by a service provider can be fetched.
     */
    public function testGetReturnsSharedItemFromServiceProvider()
    {
        $alias = 'foo';
        $item = new \stdClass;

        $container = new Container;
        $container->addServiceProvider(new Asset\SharedServiceProviderFake($alias, $item));

        $this->assertSame($item, $container->get($alias));
    }

    /**
     * Asserts that the same service provider class cannot be used to
     * register two different sets of services.
     */
    public function testSameServiceProviderClassCannotBeUsedTwice()
    {
        $alias = 'foo';
        $item = new \stdClass;

        $alias2 = 'bar';
        $item2 = new \stdClass;

        $container = new Container;
        $container->addServiceProvider(new Asset\SharedServiceProviderFake($alias, $item));
        $container->addServiceProvider(new Asset\SharedServiceProviderFake($alias2, $item2));

        $this->assertSame($item, $container->get($alias));

        $this->setExpectedException('League\Container\Exception\NotFoundException');

        $container->get($alias2);
    }

    /**
     * Asserts that the same service provider class can be used to
     * register two different sets of services when it provides its
     * own unique signature.
     */
    public function testSameServiceProviderClassCanBeUsedTwiceWithDifferentSignatures()
    {
        $alias = 'foo';
        $item = new \stdClass;
        $signature1 = 'foo';

        $alias2 = 'bar';
        $item2 = new \stdClass;
        $signature2 = 'bar';

        $container = new Container;
        $container->addServiceProvider(new Asset\SharedServiceProviderWithSignatureFake($alias, $item, $signature1));
        $container->addServiceProvider(new Asset\SharedServiceProviderWithSignatureFake($alias2, $item2, $signature2));

        $this->assertSame($item, $container->get($alias));
        $this->assertSame($item2, $container->get($alias2));
    }

    /**
     * Asserts that the container to which is delegated can resolve items from the delegating container.
     */
    public function testDelegateSharesContainer()
    {
        $container = new Container;

        $container->delegate(new ReflectionContainer);

        $container->share('League\Container\Test\Asset\Bar', function () {
            $bar = new Asset\Bar();
            $bar->setSomething('bar');
            return $bar;
        });

        $bar = $container->get('League\Container\Test\Asset\Bar');
        $foo = $container->get('League\Container\Test\Asset\Foo');

        $this->assertSame($foo->bar, $bar);
    }

    /**
     * Asserts that the extend method returns a definition.
     */
    public function testExtendReturnsDefinitions()
    {
        $sp = $this->getMock('League\Container\ServiceProvider\ServiceProviderAggregateInterface');

        $sp->expects($this->at(0))->method('setContainer')->will($this->returnSelf());
        $sp->expects($this->at(1))->method('provides')->with($this->equalTo('stdClass'))->will($this->returnValue(true));
        $sp->expects($this->at(2))->method('register')->with($this->equalTo('stdClass'));
        $sp->expects($this->at(3))->method('provides')->with($this->equalTo('closure'))->will($this->returnValue(false));

        $container = new Container($sp);

        $container->add('stdClass');
        $container->share('closure', function () {});

        $this->assertInstanceOf('League\Container\Definition\ClassDefinition', $container->extend('stdClass'));
        $this->assertInstanceOf('League\Container\Definition\CallableDefinition', $container->extend('closure'));
    }

    /**
     * Asserts that an exception is thrown when the extend method cannot find a definition to extend.
     */
    public function testExtendThrowsWhenCannotFindDefinition()
    {
        $this->setExpectedException('League\Container\Exception\NotFoundException');

        $container = new Container;

        $container->extend('something');
    }

    public function testReregisteringSharedItemWillRemoveAPreviouslyResolvedSharedItem()
    {
        // Arrange
        $container = new Container();
        $container->share('key', function () {
            return 'bar';
        });
        $container->get('key');
        $container->share('key', function () {
            return 'baz';
        });

        // Act
        $baz = $container->get('key');

        // Assert
        $this->assertEquals('baz', $baz);
    }

    public function testReregisteringWillRemoveAPreviouslyResolvedSharedItem()
    {
        // Arrange
        $container = new Container();
        $container->share('key', function () {
            return 'bar';
        });
        $container->get('key');
        $container->add('key', function () {
            return 'baz';
        });

        // Act
        $baz = $container->get('key');

        // Assert
        $this->assertEquals('baz', $baz);
    }

    public function testRegisteringShareItemAfterRegisteringNonShareItemWithSameKeyWillMakeItShared()
    {
        // Arrange
        $nonShared = 0;
        $shared = 0;

        $container = new Container();
        $container->add('key', function () use (&$nonShared) {
            $nonShared++;
            return 'non-shared';
        });
        $container->share('key', function () use (&$shared) {
            $shared++;
            return 'shared';
        });

        // Act
        $result1 = $container->get('key');
        $result2 = $container->get('key');

        // Assert
        $this->assertEquals('shared', $result1);
        $this->assertEquals('shared', $result2);
        $this->assertEquals(0, $nonShared);
        $this->assertEquals(1, $shared);
    }

    public function testRegisteringNonSharedItemAfterRegisteringSharedItemWithSameKeyWillMakeItNonShared()
    {
        // Arrange
        $nonShared = 0;
        $shared = 0;

        $container = new Container();
        $container->share('key', function () use (&$shared) {
            $shared++;
            return 'shared';
        });
        $result1 = $container->get('key');
        $result2 = $container->get('key');
        $container->add('key', function () use (&$nonShared) {
            $nonShared++;
            return 'non-shared';
        });

        // Act
        $result3 = $container->get('key');
        $result4 = $container->get('key');

        // Assert
        $this->assertEquals('shared', $result1);
        $this->assertEquals('shared', $result2);
        $this->assertEquals('non-shared', $result3);
        $this->assertEquals('non-shared', $result4);
        $this->assertEquals(2, $nonShared);
        $this->assertEquals(1, $shared);
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
