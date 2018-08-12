<?php declare(strict_types=1);

namespace League\Container\Test;

use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\{Foo, FooCallable, Bar};
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ReflectionContainerTest extends TestCase
{
    /**
     * @param array $items
     *
     * @return \Psr\Container\ContainerInterface
     */
    private function getContainerMock(array $items = [])
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

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

    /**
     * Asserts that ReflectionContainer claims it has an item if a class exists for the alias.
     */
    public function testHasReturnsTrueIfClassExists()
    {
        $container = new ReflectionContainer;

        $this->assertTrue($container->has(ReflectionContainer::class));
    }

    /**
     * Asserts that ReflectionContainer denies it has an item if a class does not exist for the alias.
     */
    public function testHasReturnsFalseIfClassDoesNotExist()
    {
        $container = new ReflectionContainer;

        $this->assertFalse($container->has('blah'));
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that does not have a constructor.
     */
    public function testContainerInstantiatesClassWithoutConstructor()
    {
        $classWithoutConstructor = \stdClass::class;

        $container = new ReflectionContainer;

        $this->assertInstanceOf($classWithoutConstructor, $container->get($classWithoutConstructor));
    }

    /**
     * Asserts that ReflectionContainer instantiates and cacheds a class that does not have a constructor.
     */
    public function testContainerInstantiatesAndCachesClassWithoutConstructor()
    {
        $classWithoutConstructor = \stdClass::class;

        $container = (new ReflectionContainer)->cacheResolutions();

        $classWithoutConstructorOne = $container->get($classWithoutConstructor);
        $classWithoutConstructorTwo = $container->get($classWithoutConstructor);

        $this->assertInstanceOf($classWithoutConstructor, $classWithoutConstructorOne);
        $this->assertInstanceOf($classWithoutConstructor, $classWithoutConstructorTwo);
        $this->assertSame($classWithoutConstructorOne, $classWithoutConstructorTwo);
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that has a constructor.
     */
    public function testGetInstantiatesClassWithConstructor()
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $container = new ReflectionContainer;

        $container->setContainer($container);

        $item = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertInstanceOf($dependencyClass, $item->bar);
    }

    /**
     * Asserts that ReflectionContainer instantiates and caches a class that has a constructor.
     */
    public function testGetInstantiatesAndCachedClassWithConstructor()
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $container = (new ReflectionContainer)->cacheResolutions();

        $container->setContainer($container);

        $itemOne = $container->get($classWithConstructor);
        $itemTwo = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $itemOne);
        $this->assertInstanceOf($dependencyClass, $itemOne->bar);

        $this->assertInstanceOf($classWithConstructor, $itemTwo);
        $this->assertInstanceOf($dependencyClass, $itemTwo->bar);

        $this->assertSame($itemOne, $itemTwo);
        $this->assertSame($itemOne->bar, $itemTwo->bar);
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that has a constructor with a type-hinted argument, and
     * fetches that dependency from the container injected into the ReflectionContainer.
     */
    public function testGetInstantiatesClassWithConstructorAndUsesContainer()
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $dependency = new $dependencyClass;
        $container  = new ReflectionContainer;

        $container->setContainer($this->getContainerMock([
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
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $dependency = new $dependencyClass;
        $container  = new ReflectionContainer;

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
        $this->expectException(NotFoundException::class);

        $container = new ReflectionContainer;

        $container->get('Whoooo');
    }

    /**
     * Asserts that call reflects on a closure and injects arguments.
     */
    public function testCallReflectsOnClosureArguments()
    {
        $container = new ReflectionContainer;

        $foo = $container->call(function (Foo $foo) {
            return $foo;
        });

        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    /**
     * Asserts that call reflects on an instance method and injects arguments.
     */
    public function testCallReflectsOnInstanceMethodArguments()
    {
        $container = new ReflectionContainer;
        $foo       = new Foo;

        $container->call([$foo, 'setBar']);

        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    /**
     * Asserts that call reflects on a static method and injects arguments.
     */
    public function testCallReflectsOnStaticMethodArguments()
    {
        $container = new ReflectionContainer;

        $container->setContainer($container);

        $container->call('League\Container\Test\Asset\Foo::staticSetBar');

        $this->assertInstanceOf('League\Container\Test\Asset\Bar', Asset\Foo::$staticBar);
        $this->assertEquals('hello world', Asset\Foo::$staticHello);
    }

    /**
     * Asserts that exception is thrown when an argument cannot be resolved.
     */
    public function testThrowsWhenArgumentCannotBeResolved()
    {
        $this->expectException(NotFoundException::class);

        $container = new ReflectionContainer;

        $container->call([new Bar, 'setSomething']);
    }

    /**
     * Tests the support for __invokable/callable classes for the ReflectionContainer::call method.
     */
    public function testInvokableClass()
    {
        $container = new ReflectionContainer;

        $foo = $container->call(new FooCallable, [new Bar]);

        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }
}
