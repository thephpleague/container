<?php

namespace League\Container\Test;

use League\Container\ImmutableContainerInterface;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset;

class ReflectionContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that ReflectionContainer claims it has an item if a class exists for the alias.
     */
    public function testHasReturnsTrueIfClassExists()
    {
        $container = new ReflectionContainer;

        $this->assertTrue($container->has('League\Container\Test\Asset\Bar'));
    }

    /**
     * Asserts that ReflectionContainer denies it has an item if a class does not exist for the alias.
     */
    public function testHasReturnsFalseIfClassDoesNotExist()
    {
        $container = new ReflectionContainer;

        $this->assertFalse($container->has('Foo\Bar\Baz'));
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that does not have a constructor.
     */
    public function testGetInstantiatesClassWithoutConstructor()
    {
        $classWithoutConstructor = 'League\Container\Test\Asset\Bar';

        $container = new ReflectionContainer;

        $this->assertInstanceOf($classWithoutConstructor, $container->get($classWithoutConstructor));
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that has a constructor.
     */
    public function testGetInstantiatesClassWithConstructor()
    {
        $classWithConstructor = 'League\Container\Test\Asset\Foo';
        $dependencyClass = 'League\Container\Test\Asset\Bar';

        $container = new ReflectionContainer;

        $item = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertInstanceOf($dependencyClass, $item->bar);
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that has a constructor with a type-hinted argument, and
     * fetches that dependency from the container injected into the ReflectionContainer.
     */
    public function testGetInstantiatesClassWithConstructorAndUsesContainer()
    {
        $classWithConstructor = 'League\Container\Test\Asset\Foo';
        $dependencyClass = 'League\Container\Test\Asset\Bar';
        $dependency = new $dependencyClass;

        $container = new ReflectionContainer;

        $container->setContainer($this->getImmutableContainerMock([
            $dependencyClass => $dependency,
        ]));

        $item = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertSame($dependency, $item->bar);
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that has a constructor with a type-hinted argument, and
     * uses the values provided in the argument array.
     */
    public function testGetInstantiatesClassWithConstructorAndUsesArguments()
    {
        $classWithConstructor = 'League\Container\Test\Asset\Foo';
        $dependencyClass = 'League\Container\Test\Asset\Bar';
        $dependency = new $dependencyClass;

        $container = new ReflectionContainer;

        $item = $container->get($classWithConstructor, [
            'bar' => $dependency
        ]);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertSame($dependency, $item->bar);
    }

    /**
     * Asserts that an exception is thrown when attempting to get a class that does not exist.
     */
    public function testThrowsWhenGettingNonExistentClass()
    {
        $this->setExpectedException('League\Container\Exception\NotFoundException');

        $container = new ReflectionContainer;

        $container->get('Whoooo');
    }

    /**
     * Asserts that call reflects on a closure and injects arguments.
     */
    public function testCallReflectsOnClosureArguments()
    {
        $container = new ReflectionContainer;

        $foo = $container->call(function (Asset\Foo $foo) {
            return $foo;
        });

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }

    /**
     * Asserts that call reflects on an instance method and injects arguments.
     */
    public function testCallReflectsOnInstanceMethodArguments()
    {
        $container = new ReflectionContainer;

        $foo = new Asset\Foo;

        $container->call([$foo, 'setBar']);

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }

    /**
     * Asserts that call reflects on a static method and injects arguments.
     */
    public function testCallReflectsOnStaticMethodArguments()
    {
        $container = new ReflectionContainer;

        $container->call('League\Container\Test\Asset\Foo::staticSetBar');

        $this->assertInstanceOf('League\Container\Test\Asset\Bar', Asset\Foo::$staticBar);
        $this->assertEquals('hello world', Asset\Foo::$staticHello);
    }

    /**
     * Asserts that exception is thrown when an argument cannot be resolved.
     */
    public function testThrowsWhenArgumentCannotBeResolved()
    {
        $this->setExpectedException('League\Container\Exception\NotFoundException');

        $container = new ReflectionContainer;

        $container->call([new Asset\Bar, 'setSomething']);
    }

    /**
     * Tests the support for __invokable/callable classes for the ReflectionContainer::call method.
     */
    public function testInvokableClass()
    {
        $container = new ReflectionContainer;

        $container->call(new Asset\FooWithNamedConstructor, [new Asset\Bar()]);
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
            });

        $container
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($alias) use ($items) {
                if (array_key_exists($alias, $items)) {
                    return $items[$alias];
                }
            });

        return $container;
    }
}
