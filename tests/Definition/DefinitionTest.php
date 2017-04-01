<?php declare(strict_types=1);

namespace League\Container\Test\Definition;

use League\Container\Argument\{ClassName, RawArgument};
use League\Container\Definition\Definition;
use League\Container\Test\Asset\{Foo, FooCallable, Bar};
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DefinitionTest extends TestCase
{
    /**
     * Asserts that the definition can resolve a closure with defined args.
     */
    public function testDefinitionResolvesClosureWithDefinedArgs()
    {
        $definition = new Definition('callable', function (...$args) {
            return implode(' ', $args);
        });

        $definition->addArguments(['hello', 'world']);

        $actual = $definition->resolve();

        $this->assertSame('hello world', $actual);
    }

    /**
     * Asserts that the definition can resolve a closure with runtime args.
     */
    public function testDefinitionResolvesClosureWithRuntimedArgs()
    {
        $definition = new Definition('callable', function (...$args) {
            return implode(' ', $args);
        });

        $definition->addArguments(['hello', 'world']);

        $actual = $definition->resolve(['goodbye', 'earth']);

        $this->assertSame('goodbye earth', $actual);
    }

    /**
     * Asserts that the definition can resolve a closure returning raw argument.
     */
    public function testDefinitionResolvesClosureReturningRawArgument()
    {
        $definition = new Definition('callable', function () {
            return new RawArgument('hello world');
        });

        $actual = $definition->resolve();

        $this->assertSame('hello world', $actual);
    }

    /**
     * Asserts that the definition can resolve a callable class.
     */
    public function testDefinitionResolvesCallableClass()
    {
        $definition = new Definition('callable', new FooCallable);

        $definition->addArgument(new Bar);

        $actual = $definition->resolve();

        $this->assertInstanceOf(Foo::class, $actual);
    }

    /**
     * Asserts that the definition can resolve an array callable.
     */
    public function testDefinitionResolvesArrayCallable()
    {
        $definition = new Definition('callable', [new FooCallable, '__invoke']);

        $definition->addArgument(new Bar);

        $actual = $definition->resolve();

        $this->assertInstanceOf(Foo::class, $actual);
    }

    /**
     * Asserts that the definition can resolve a class method calls.
     */
    public function testDefinitionResolvesClassWithMethodCalls()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $bar = new Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->will($this->returnValue($bar));

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);
        $definition->addMethodCalls(['setBar' => [Bar::class]]);

        $actual = $definition->resolve();

        $this->assertInstanceOf(Foo::class, $actual);
        $this->assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * Asserts that the definition can resolve a class with defined args.
     */
    public function testDefinitionResolvesClassWithDefinedArgs()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $bar = new Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->will($this->returnValue($bar));

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);
        $definition->addArgument(Bar::class);

        $actual = $definition->resolve();

        $this->assertInstanceOf(Foo::class, $actual);
        $this->assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * Asserts that the definition can resolve a class with runtime args.
     */
    public function testDefinitionResolvesClassWithRuntimeArgs()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $bar = new Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->will($this->returnValue($bar));

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);

        $actual = $definition->resolve([Bar::class]);

        $this->assertInstanceOf(Foo::class, $actual);
        $this->assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * Asserts that the definition can resolve a class as class name.
     */
    public function testDefinitionResolvesClassAsClassName()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $bar = new Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->will($this->returnValue($bar));

        $definition = new Definition('callable', new ClassName(Foo::class));

        $definition->setContainer($container);
        $definition->addArgument(new ClassName(Bar::class));

        $actual = $definition->resolve();

        $this->assertInstanceOf(Foo::class, $actual);
        $this->assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * Asserts that the definition resolves a shared item only once.
     */
    public function testDefinitionResolvesSharedItemOnlyOnce()
    {
        $definition = new Definition('callable', new ClassName(Foo::class));

        $definition->setShared(true);

        $actual1 = $definition->resolve();
        $actual2 = $definition->resolve();
        $actual3 = $definition->resolve([], true);

        $this->assertSame($actual1, $actual2);
        $this->assertFalse($actual1 === $actual3);
    }
}