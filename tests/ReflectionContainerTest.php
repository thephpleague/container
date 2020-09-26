<?php

declare(strict_types=1);

namespace League\Container\Test;

use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\{Foo, FooCallable, Bar};
use PHPUnit\Framework\TestCase;
use League\Container\Container;

class ReflectionContainerTest extends TestCase
{
    /**
     * @param array $items
     *
     * @return Container
     */
    private function getContainerMock(array $items = []): Container
    {
        $container = $this->getMockBuilder(Container::class)->getMock();

        $container
            ->method('has')
            ->willReturnCallback(function ($alias) use ($items) {
                return array_key_exists($alias, $items);
            })
        ;

        $container
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
    public function testHasReturnsTrueIfClassExists(): void
    {
        $container = new ReflectionContainer();
        self::assertTrue($container->has(ReflectionContainer::class));
    }

    /**
     * Asserts that ReflectionContainer denies it has an item if a class does not exist for the alias.
     */
    public function testHasReturnsFalseIfClassDoesNotExist(): void
    {
        $container = new ReflectionContainer();
        self::assertFalse($container->has('blah'));
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that does not have a constructor.
     */
    public function testContainerInstantiatesClassWithoutConstructor(): void
    {
        $classWithoutConstructor = \stdClass::class;
        $container = new ReflectionContainer();
        self::assertInstanceOf($classWithoutConstructor, $container->get($classWithoutConstructor));
    }

    /**
     * Asserts that ReflectionContainer instantiates and caches a class that does not have a constructor.
     */
    public function testContainerInstantiatesAndCachesClassWithoutConstructor(): void
    {
        $classWithoutConstructor = \stdClass::class;
        $container = new ReflectionContainer(true);

        $classWithoutConstructorOne = $container->get($classWithoutConstructor);
        $classWithoutConstructorTwo = $container->get($classWithoutConstructor);

        self::assertInstanceOf($classWithoutConstructor, $classWithoutConstructorOne);
        self::assertInstanceOf($classWithoutConstructor, $classWithoutConstructorTwo);
        self::assertSame($classWithoutConstructorOne, $classWithoutConstructorTwo);
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that has a constructor.
     */
    public function testGetInstantiatesClassWithConstructor(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $container = new ReflectionContainer();
        $item = $container->get($classWithConstructor);

        self::assertInstanceOf($classWithConstructor, $item);
        self::assertInstanceOf($dependencyClass, $item->bar);
    }

    /**
     * Asserts that ReflectionContainer instantiates and caches a class that has a constructor.
     */
    public function testGetInstantiatesAndCachedClassWithConstructor(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $container = new ReflectionContainer(true);

        $itemOne = $container->get($classWithConstructor);
        $itemTwo = $container->get($classWithConstructor);

        self::assertInstanceOf($classWithConstructor, $itemOne);
        self::assertInstanceOf($dependencyClass, $itemOne->bar);

        self::assertInstanceOf($classWithConstructor, $itemTwo);
        self::assertInstanceOf($dependencyClass, $itemTwo->bar);

        self::assertSame($itemOne, $itemTwo);
        self::assertSame($itemOne->bar, $itemTwo->bar);
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that has a constructor with a type-hinted argument, and
     * fetches that dependency from the container injected into the ReflectionContainer.
     */
    public function testGetInstantiatesClassWithConstructorAndUsesContainer(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $dependency = new $dependencyClass();
        $container  = new ReflectionContainer();

        $container->setContainer($this->getContainerMock([
            $dependencyClass => $dependency,
        ]));

        $item = $container->get($classWithConstructor);

        self::assertInstanceOf($classWithConstructor, $item);
        self::assertSame($dependency, $item->bar);
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that has a constructor with a type-hinted argument, and
     * uses the values provided in the argument array.
     */
    public function testGetInstantiatesClassWithConstructorAndUsesArguments(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $dependency = new $dependencyClass();
        $container  = new ReflectionContainer();

        $item = $container->get($classWithConstructor, [
            'bar' => $dependency
        ]);

        self::assertInstanceOf($classWithConstructor, $item);
        self::assertSame($dependency, $item->bar);
    }

    /**
     * Asserts that an exception is thrown when attempting to get a class that does not exist.
     */
    public function testThrowsWhenGettingNonExistentClass(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new ReflectionContainer();
        $container->get('Whoooo');
    }

    /**
     * Asserts that call reflects on a closure and injects arguments.
     */
    public function testCallReflectsOnClosureArguments(): void
    {
        $container = new ReflectionContainer();

        $foo = $container->call(function (Foo $foo) {
            return $foo;
        });

        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    /**
     * Asserts that call reflects on an instance method and injects arguments.
     */
    public function testCallReflectsOnInstanceMethodArguments(): void
    {
        $container = new ReflectionContainer();
        $foo       = new Foo();
        $container->call([$foo, 'setBar']);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    /**
     * Asserts that call reflects on a static method and injects arguments.
     */
    public function testCallReflectsOnStaticMethodArguments(): void
    {
        $container = new ReflectionContainer();
        $container->call('League\Container\Test\Asset\Foo::staticSetBar');
        self::assertInstanceOf(Bar::class, Asset\Foo::$staticBar);
        self::assertEquals('hello world', Asset\Foo::$staticHello);
    }

    /**
     * Asserts that exception is thrown when an argument cannot be resolved.
     */
    public function testThrowsWhenArgumentCannotBeResolved(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new ReflectionContainer();
        $container->call([new Bar(), 'setSomething']);
    }

    /**
     * Tests the support for __invokable/callable classes for the ReflectionContainer::call method.
     */
    public function testInvokableClass(): void
    {
        $container = new ReflectionContainer();
        $foo = $container->call(new FooCallable(), [new Bar()]);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }
}
