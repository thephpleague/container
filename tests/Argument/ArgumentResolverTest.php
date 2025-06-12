<?php

declare(strict_types=1);

namespace League\Container\Test\Argument;

use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\Argument\Literal;
use League\Container\Container;
use League\Container\ContainerAwareTrait;
use PHPUnit\Framework\TestCase;

class ArgumentResolverTest extends TestCase
{
    public function testResolverResolvesFromContainer(): void
    {
        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $container = $this->getMockBuilder(Container::class)->getMock();

        $matcher = $this->exactly(2);

        $container
            ->expects($matcher)
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $container->expects($this->once())->method('get')->with($this->equalTo('alias1'))->willReturn($resolver);

        $resolver->setContainer($container);

        $args = $resolver->resolveArguments(['alias1', 'alias2']);

        $this->assertSame($resolver, $args[0]);
        $this->assertSame('alias2', $args[1]);
    }

    public function testResolverResolvesLiteralArguments(): void
    {
        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $container = $this->getMockBuilder(Container::class)->getMock();

        $container
            ->expects($this->once())
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('alias1'))
            ->willReturn(new Literal\StringArgument('value1'))
        ;

        $resolver->setContainer($container);

        $args = $resolver->resolveArguments(['alias1', new Literal\StringArgument('value2')]);

        $this->assertSame('value1', $args[0]);
        $this->assertSame('value2', $args[1]);
    }
}
