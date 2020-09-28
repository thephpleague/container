<?php

declare(strict_types=1);

namespace League\Container\Test\Definition;

use League\Container\Argument\Literal;
use League\Container\Definition\Definition;
use League\Container\Test\Asset\{Foo, FooCallable, Bar};
use PHPUnit\Framework\TestCase;
use League\Container\Container;

class DefinitionTest extends TestCase
{
    /**
     * Asserts that the definition can resolve a closure with defined args.
     */
    public function testDefinitionResolvesClosureWithDefinedArgs(): void
    {
        $definition = new Definition('callable', function (...$args) {
            return implode(' ', $args);
        });

        $definition->addArguments(['hello', 'world']);

        $actual = $definition->resolve();

        self::assertSame('hello world', $actual);
    }

    /**
     * Asserts that the definition can resolve a closure returning raw argument.
     */
    public function testDefinitionResolvesClosureReturningRawArgument(): void
    {
        $definition = new Definition('callable', function () {
            return new Literal\StringArgument('hello world');
        });

        $actual = $definition->resolve();

        self::assertSame('hello world', $actual);
    }

    /**
     * Asserts that the definition can resolve a callable class.
     */
    public function testDefinitionResolvesCallableClass(): void
    {
        $definition = new Definition('callable', new FooCallable());
        $definition->addArgument(new Bar());
        $actual = $definition->resolve();

        self::assertInstanceOf(Foo::class, $actual);
    }

    /**
     * Asserts that the definition can resolve an array callable.
     */
    public function testDefinitionResolvesArrayCallable(): void
    {
        $definition = new Definition('callable', [new FooCallable(), '__invoke']);
        $definition->addArgument(new Bar());
        $actual = $definition->resolve();

        self::assertInstanceOf(Foo::class, $actual);
    }

    /**
     * Asserts that the definition can resolve a class method calls.
     */
    public function testDefinitionResolvesClassWithMethodCalls(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $bar = new Bar();

        $container->expects(self::once())->method('has')->with(self::equalTo(Bar::class))->willReturn(true);
        $container->expects(self::once())->method('get')->with(self::equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);
        $definition->addMethodCalls(['setBar' => [Bar::class]]);

        $actual = $definition->resolve();

        self::assertInstanceOf(Foo::class, $actual);
        self::assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * Asserts that the definition can resolve a class with defined args.
     */
    public function testDefinitionResolvesClassWithDefinedArgs(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $bar = new Bar();

        $container->expects(self::once())->method('has')->with(self::equalTo(Bar::class))->willReturn(true);
        $container->expects(self::once())->method('get')->with(self::equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);
        $definition->addArgument(Bar::class);

        $actual = $definition->resolve();

        self::assertInstanceOf(Foo::class, $actual);
        self::assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * Asserts that the definition resolves a shared item only once.
     */
    public function testDefinitionResolvesSharedItemOnlyOnce(): void
    {
        $definition = new Definition('class', Foo::class);

        $definition->setShared(true);

        $actual1 = $definition->resolve();
        $actual2 = $definition->resolve();
        $actual3 = $definition->resolve(true);

        self::assertSame($actual1, $actual2);
        self::assertNotSame($actual1, $actual3);
    }

    /**
     * Asserts that the definition can add tags.
     */
    public function testDefinitionCanAddTags(): void
    {
        $definition = new Definition('class', Foo::class);

        $definition->addTag('tag1')->addTag('tag2');

        self::assertTrue($definition->hasTag('tag1'));
        self::assertTrue($definition->hasTag('tag2'));
        self::assertFalse($definition->hasTag('tag3'));
    }

    /**
     * Assert that the definition returns the concrete.
     */
    public function testDefinitionCanGetConcrete(): void
    {
        $concrete = new Literal\StringArgument(Foo::class);
        $definition = new Definition('class', $concrete);

        self::assertSame($concrete, $definition->getConcrete());
    }

    /**
     * Assert that the definition set the concrete.
     */
    public function testDefinitionCanSetConcrete(): void
    {
        $definition = new Definition('class', null);

        $concrete = new Literal\StringArgument(Foo::class);
        $definition->setConcrete($concrete);

        self::assertSame($concrete, $definition->getConcrete());
    }
}
