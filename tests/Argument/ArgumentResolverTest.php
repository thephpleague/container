<?php

declare(strict_types=1);

namespace League\Container\Test\Argument;

use League\Container\Argument\{ArgumentResolverInterface, ArgumentResolverTrait, Typed};
use League\Container\{Container, ContainerAwareTrait};
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;

class ArgumentResolverTest extends TestCase
{
    /**
     * Asserts that the resolver proxies to container for resolution.
     */
    public function testResolverResolvesFromContainer(): void
    {
        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $container = $this->getMockBuilder(Container::class)->getMock();

        $container
            ->expects(self::at(0))
            ->method('has')
            ->with(self::equalTo('alias1'))
            ->willReturn(true)
        ;

        $container->expects(self::at(1))->method('get')->with(self::equalTo('alias1'))->willReturn($resolver);
        $container->expects(self::at(2))->method('has')->with(self::equalTo('alias2'))->willReturn(false);

        /** @var Container $container */
        $resolver->setContainer($container);

        $args = $resolver->resolveArguments(['alias1', 'alias2']);

        $this->assertSame($resolver, $args[0]);
        $this->assertSame('alias2', $args[1]);
    }

    /**
     * Asserts that the resolver resolves raw arguments.
     */
    public function testResolverResolvesResolvesRawArguments(): void
    {
        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $container = $this->getMockBuilder(Container::class)->getMock();

        $container
            ->expects(self::at(0))
            ->method('has')
            ->with(self::equalTo('alias1'))
            ->willReturn(true)
        ;

        $container
            ->expects(self::at(1))
            ->method('get')
            ->with(self::equalTo('alias1'))
            ->willReturn(new Typed\StringArgument('value1'))
        ;

        /** @var Container $container */
        $resolver->setContainer($container);

        $args = $resolver->resolveArguments(['alias1', new Typed\StringArgument('value2')]);

        self::assertSame('value1', $args[0]);
        self::assertSame('value2', $args[1]);
    }

    /**
     * Asserts that the resolver can resolve arguments via reflection.
     */
    public function testResolverResolvesArgumentsViaReflection(): void
    {
        $method    = $this->getMockBuilder(ReflectionFunctionAbstract::class)->getMock();
        $param1    = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
        $param2    = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
        $param3    = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
        $class     = $this->getMockBuilder(ReflectionNamedType::class)->disableOriginalConstructor()->getMock();
        $container = $this->getMockBuilder(Container::class)->getMock();

        $class->expects(self::once())->method('getName')->willReturn('Class');
        $param1->expects(self::once())->method('getName')->willReturn('param1');
        $param1->expects(self::once())->method('getType')->willReturn($class);

        $param2->expects(self::once())->method('getName')->willReturn('param2');
        $param2->expects(self::once())->method('getType')->willReturn(null);
        $param2->expects(self::once())->method('isDefaultValueAvailable')->willReturn(true);
        $param2->expects(self::once())->method('getDefaultValue')->willReturn('value2');

        $param3->expects(self::once())->method('getName')->willReturn('param3');

        $method->expects(self::once())->method('getParameters')->willReturn([$param1, $param2, $param3]);

        $container->expects(self::at(0))->method('has')->with(self::equalTo('Class'))->willReturn(false);
        $container->expects(self::at(1))->method('has')->with(self::equalTo('value2'))->willReturn(false);
        $container->expects(self::at(2))->method('has')->with(self::equalTo('value3'))->willReturn(false);

        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        /** @var Container $container */
        $resolver->setContainer($container);

        $args = $resolver->reflectArguments($method, ['param3' => 'value3']);

        self::assertSame('Class', $args[0]);
        self::assertSame('value2', $args[1]);
        self::assertSame('value3', $args[2]);
    }

    /**
     * Asserts that the resolver throws an exception when reflection can't resolve a value.
     */
    public function testResolverThrowsExceptionWhenReflectionDoesNotResolve(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $method = $this->getMockBuilder(ReflectionFunctionAbstract::class)->getMock();
        $param  = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();

        $param->expects(self::once())->method('getName')->willReturn('param1');
        $param->expects(self::once())->method('getType')->willReturn(null);
        $param->expects(self::once())->method('isDefaultValueAvailable')->willReturn(false);

        $method->expects(self::once())->method('getParameters')->willReturn([$param]);

        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        /** @var ReflectionFunctionAbstract $method */
        $resolver->reflectArguments($method);
    }
}
