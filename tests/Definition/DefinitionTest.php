<?php declare(strict_types=1);

namespace League\Container\Test\Definition;

use League\Container\Argument\{ClassName, RawArgument};
use League\Container\Definition\Definition;
use League\Container\Test\Asset\{Foo, FooCallable, Bar};
use PHPUnit\Framework\TestCase;
use League\Container\Container;
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
     * Asserts that the definition can resolve a closure without explicit arguments.
     */
    public function testDefinitionResolvesClosureWithoutArguments()
    {
        $container = $this->getMockBuilder(Container::class)->getMock();

        $container->expects($this->once())->method('has')->with($this->equalTo(ContainerInterface::class))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo(ContainerInterface::class))->willReturn($container);

        $definition = new Definition('callable', function (ContainerInterface $container) {
            return $container;
        });
        $definition->setLeagueContainer($container);

        $actual = $definition->resolve();

        $this->assertInstanceOf(ContainerInterface::class, $actual);
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
        $container = $this->getMockBuilder(Container::class)->getMock();

        $bar = new Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', Foo::class);

        $definition->setLeagueContainer($container);
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
        $container = $this->getMockBuilder(Container::class)->getMock();

        $bar = new Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', Foo::class);

        $definition->setLeagueContainer($container);
        $definition->addArgument(Bar::class);

        $actual = $definition->resolve();

        $this->assertInstanceOf(Foo::class, $actual);
        $this->assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * Asserts that the definition can resolve a class as class name.
     */
    public function testDefinitionResolvesClassAsClassName()
    {
        $container = $this->getMockBuilder(Container::class)->getMock();

        $bar = new Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', new ClassName(Foo::class));

        $definition->setLeagueContainer($container);
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
        $actual3 = $definition->resolve(true);

        $this->assertSame($actual1, $actual2);
        $this->assertNotSame($actual1, $actual3);
    }

    /**
     * Asserts that the definition can add tags.
     */
    public function testDefinitionCanAddTags()
    {
        $definition = new Definition('callable', new ClassName(Foo::class));

        $definition->addTag('tag1')->addTag('tag2');

        $this->assertTrue($definition->hasTag('tag1'));
        $this->assertTrue($definition->hasTag('tag2'));
        $this->assertFalse($definition->hasTag('tag3'));
    }

    /**
     * Assert that the definition returns the concrete.
     */
    public function testDefinitionCanGetConcrete()
    {
        $concrete = new ClassName(Foo::class);
        $definition = new Definition('callable', $concrete);

        $this->assertSame($concrete, $definition->getConcrete());
    }

    /**
     * Assert that the definition set the concrete.
     */
    public function testDefinitionCanSetConcrete()
    {
        $definition = new Definition('callable', null);

        $concrete = new ClassName(Foo::class);
        $definition->setConcrete($concrete);

        $this->assertSame($concrete, $definition->getConcrete());
    }
}
