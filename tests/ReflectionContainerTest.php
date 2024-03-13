<?php

declare(strict_types=1);

namespace League\Container\Test;

use League\Container\Container;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\{Foo, FooCallable, Bar, ProFoo, ProBar};
use PHPUnit\Framework\TestCase;

class ReflectionContainerTest extends TestCase
{
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

    public function testHasReturnsTrueIfClassExists(): void
    {
        $container = new ReflectionContainer();
        $this->assertTrue($container->has(ReflectionContainer::class));
    }

    public function testHasReturnsFalseIfClassDoesNotExist(): void
    {
        $container = new ReflectionContainer();
        $this->assertFalse($container->has('blah'));
    }

    public function testContainerInstantiatesClassWithoutConstructor(): void
    {
        $classWithoutConstructor = \stdClass::class;
        $container = new ReflectionContainer();
        $this->assertInstanceOf($classWithoutConstructor, $container->get($classWithoutConstructor));
    }

    public function testContainerInstantiatesAndCachesClassWithoutConstructor(): void
    {
        $classWithoutConstructor = \stdClass::class;
        $container = new ReflectionContainer(true);

        $classWithoutConstructorOne = $container->get($classWithoutConstructor);
        $classWithoutConstructorTwo = $container->get($classWithoutConstructor);

        $this->assertInstanceOf($classWithoutConstructor, $classWithoutConstructorOne);
        $this->assertInstanceOf($classWithoutConstructor, $classWithoutConstructorTwo);
        $this->assertSame($classWithoutConstructorOne, $classWithoutConstructorTwo);
    }

    public function testGetInstantiatesClassWithConstructor(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $container = new ReflectionContainer();
        $item = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertInstanceOf($dependencyClass, $item->bar);
    }

    public function testGetInstantiatesAndCachedClassWithConstructor(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $container = new ReflectionContainer(true);

        $itemOne = $container->get($classWithConstructor);
        $itemTwo = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $itemOne);
        $this->assertInstanceOf($dependencyClass, $itemOne->bar);

        $this->assertInstanceOf($classWithConstructor, $itemTwo);
        $this->assertInstanceOf($dependencyClass, $itemTwo->bar);

        $this->assertSame($itemOne, $itemTwo);
        $this->assertSame($itemOne->bar, $itemTwo->bar);
    }

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

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertSame($dependency, $item->bar);
    }

    public function testGetInstantiatesClassWithConstructorAndUsesArguments(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass      = Bar::class;

        $dependency = new $dependencyClass();
        $container  = new ReflectionContainer();

        $item = $container->get($classWithConstructor, [
            'bar' => $dependency
        ]);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertSame($dependency, $item->bar);
    }

    public function testThrowsWhenGettingNonExistentClass(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new ReflectionContainer();
        $container->get('Whoooo');
    }

    public function testCallReflectsOnClosureArguments(): void
    {
        $container = new ReflectionContainer();

        $foo = $container->call(function (Foo $foo) {
            return $foo;
        });

        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testCallReflectsOnInstanceMethodArguments(): void
    {
        $container = new ReflectionContainer();
        $foo       = new Foo();
        $container->call([$foo, 'setBar']);
        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testCallReflectsOnStaticMethodArguments(): void
    {
        $container = new ReflectionContainer();
        $container->call('League\Container\Test\Asset\Foo::staticSetBar');
        $this->assertInstanceOf(Bar::class, Asset\Foo::$staticBar);
        $this->assertEquals('hello world', Asset\Foo::$staticHello);
    }

    public function testCallThrowsWhenArgumentCannotBeResolved(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new ReflectionContainer();
        $container->call([new Bar(), 'setSomething']);
    }

    public function testCallResolvesInvokableClass(): void
    {
        $container = new ReflectionContainer();
        $foo = $container->call(new FooCallable(), [new Bar()]);
        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testCallResolvesFunction(): void
    {
        $container = new ReflectionContainer();
        $foo = $container->call(Asset\test::class, [new Bar()]);
        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testGetInstantiatesClassWithConstructorAndSkipsProtectedConstructor(): void
    {
        $classWithConstructor = ProFoo::class;

        $container = new Container();
        $container->delegate(new ReflectionContainer());

        $item = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertNull($item->bar);
    }

    public function testGetInstantiatesClassWithConstructorAndUsesFactory(): void
    {
        $classWithConstructor = ProFoo::class;
        $dependencyClass = ProBar::class;

        $container = new Container();
        $container->delegate(new ReflectionContainer());

        $container->add($dependencyClass, [$dependencyClass, 'factory']);

        $item = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertInstanceOf($dependencyClass, $item->bar);
    }
}
