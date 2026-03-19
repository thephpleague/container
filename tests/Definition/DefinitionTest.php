<?php

declare(strict_types=1);

namespace League\Container\Test\Definition;

use League\Container\Argument\Literal;
use League\Container\Argument\ResolvableArgument;
use League\Container\Container;
use League\Container\Definition\Definition;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\BarInterface;
use League\Container\Test\Asset\Foo;
use League\Container\Test\Asset\FooCallable;
use League\Container\Test\Asset\FooWithRequiredDependency;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

class DefinitionTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionResolvesClosureWithDefinedArgs(): void
    {
        $definition = new Definition('callable', function (...$args) {
            return implode(' ', $args);
        });

        $definition->addArguments(['hello', 'world']);
        $actual = $definition->resolve();
        $this->assertSame('hello world', $actual);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionResolvesClosureReturningRawArgument(): void
    {
        $definition = new Definition('callable', function () {
            return new Literal\StringArgument('hello world');
        });

        $actual = $definition->resolve();
        $this->assertSame('hello world', $actual);
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionResolvesCallableClass(): void
    {
        $definition = new Definition('callable', new FooCallable());
        $definition->addArgument(new Bar());
        $actual = $definition->resolve();
        $this->assertInstanceOf(Foo::class, $actual);
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionResolvesArrayCallable(): void
    {
        $definition = new Definition('callable', [new FooCallable(), '__invoke']);
        $definition->addArgument(new Bar());
        $actual = $definition->resolve();
        $this->assertInstanceOf(Foo::class, $actual);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionResolvesClassWithMethodCalls(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $bar = new Bar();

        $container->method('has')->willReturnMap([
            [Foo::class, false],
            [Bar::class, true],
        ]);
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);
        $definition->addMethodCalls(['setBar' => [Bar::class]]);

        $actual = $definition->resolve();
        $this->assertInstanceOf(Foo::class, $actual);
        $this->assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionResolvesClassWithDefinedArgs(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $bar = new Bar();

        $container->method('has')->willReturnMap([
            [Foo::class, false],
            [Bar::class, true],
        ]);
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);
        $definition->addArgument(Bar::class);

        $actual = $definition->resolve();
        $this->assertInstanceOf(Foo::class, $actual);
        $this->assertInstanceOf(Bar::class, $actual->bar);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testDefinitionResolvesSharedItemOnlyOnce(): void
    {
        $definition = new Definition('class', Foo::class);
        $definition->setShared();
        $actual1 = $definition->resolve();
        $actual2 = $definition->resolve();
        $actual3 = $definition->resolveNew();
        $this->assertSame($actual1, $actual2);
        $this->assertNotSame($actual1, $actual3);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testDefinitionResolvesNestedAlias(): void
    {
        $aliasDefinition = new Definition('alias', new ResolvableArgument('class'));
        $definition = new Definition('class', Foo::class);
        $container = $this->getMockBuilder(Container::class)->getMock();

        $expected = $definition->resolve();

        $container->expects($this->once())->method('has')->with($this->equalTo('class'))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo('class'))->willReturn($expected);

        $aliasDefinition->setContainer($container);
        $actual = $aliasDefinition->resolve();
        $this->assertSame($expected, $actual);
    }

    public function testDefinitionCanAddTags(): void
    {
        $definition = new Definition('class', Foo::class);
        $definition->addTag('tag1')->addTag('tag2');
        $this->assertTrue($definition->hasTag('tag1'));
        $this->assertTrue($definition->hasTag('tag2'));
        $this->assertFalse($definition->hasTag('tag3'));
    }

    public function testDefinitionCanGetConcrete(): void
    {
        $concrete = new Literal\StringArgument(Foo::class);
        $definition = new Definition('class', $concrete);
        $this->assertSame($concrete, $definition->getConcrete());
    }

    public function testDefinitionCanSetConcrete(): void
    {
        $definition = new Definition('class', null);
        $concrete = new Literal\StringArgument(Foo::class);
        $definition->setConcrete($concrete);
        $this->assertSame($concrete, $definition->getConcrete());
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testNonExistentClassIsReturnedAsIdenticalString(): void
    {
        $nonExistent = 'NonExistent';
        $definition = new Definition($nonExistent);

        self::assertSame($nonExistent, $definition->getAlias());
        self::assertSame($nonExistent, $definition->resolve());
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionDelegatesToContainerForDifferentConcrete(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $bar = new Bar();

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition(BarInterface::class, Bar::class);
        $definition->setContainer($container);

        $actual = $definition->resolveNew();

        $this->assertInstanceOf(Bar::class, $actual);
        $this->assertSame($bar, $actual);
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionResolvesOwnClassWhenConcreteMatchesId(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();

        $container->expects($this->never())->method('has');
        $container->expects($this->never())->method('get');

        $definition = new Definition(Foo::class, Foo::class);
        $definition->setContainer($container);

        $actual = $definition->resolveNew();

        $this->assertInstanceOf(Foo::class, $actual);
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDefinitionDelegatesToContainerWhenConcreteComesFromResolvableArgument(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $bar = new Bar();

        $container->expects($this->once())->method('has')->with($this->equalTo(Bar::class))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition(BarInterface::class, new ResolvableArgument(Bar::class));
        $definition->setContainer($container);

        $actual = $definition->resolveNew();

        $this->assertInstanceOf(Bar::class, $actual);
        $this->assertSame($bar, $actual);
    }

    public function testResolveClassThrowsContainerExceptionForUnsatisfiedDependencies(): void
    {
        $definition = new Definition(FooWithRequiredDependency::class);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('unsatisfied dependencies');

        $definition->resolveNew();
    }
}
