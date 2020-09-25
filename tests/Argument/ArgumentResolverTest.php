<?php declare(strict_types=1);

namespace League\Container\Test;

use League\Container\Argument\{ArgumentResolverInterface, ArgumentResolverTrait, RawArgument};
use League\Container\{Container, ContainerAwareTrait};
use League\Container\Test\Asset\Qux;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionType;

class ArgumentResolverTest extends TestCase
{
    /**
     * Asserts that the resolver proxies to container for resolution.
     */
    public function testResolverResolvesFromContainer()
    {
        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $container = $this->getMockBuilder(Container::class)->getMock();

        $container->expects($this->at(0))->method('has')->with($this->equalTo('alias1'))->willReturn(true);
        $container->expects($this->at(1))->method('get')->with($this->equalTo('alias1'))->willReturn($resolver);
        $container->expects($this->at(2))->method('has')->with($this->equalTo('alias2'))->willReturn(false);

        $resolver->setLeagueContainer($container);

        $args = $resolver->resolveArguments(['alias1', 'alias2']);

        $this->assertSame($resolver, $args[0]);
        $this->assertSame('alias2', $args[1]);
    }

    /**
     * Asserts that the resolver resolves raw arguments.
     */
    public function testResolverResolvesResolvesRawArguments()
    {
        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $container = $this->getMockBuilder(Container::class)->getMock();

        $container->expects($this->at(0))->method('has')->with($this->equalTo('alias1'))->willReturn(true);
        $container->expects($this->at(1))->method('get')->with($this->equalTo('alias1'))->willReturn('value1');

        $resolver->setLeagueContainer($container);

        $args = $resolver->resolveArguments(['alias1', new RawArgument('value2')]);

        $this->assertSame('value1', $args[0]);
        $this->assertSame('value2', $args[1]);
    }

    /**
     * Asserts that the resolver can resolve arguments via reflection.
     */
    public function testResolverResolvesArgumentsViaReflection()
    {
        $method    = $this->getMockBuilder(ReflectionFunctionAbstract::class)->getMock();
        $param1    = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
        $param2    = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
        $param3    = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
        $class     = $this->getMockBuilder(ReflectionType::class)->disableOriginalConstructor()->getMock();
        $container = $this->getMockBuilder(Container::class)->getMock();

        if (PHP_VERSION_ID >= 70400) {
            $class->expects($this->once())->method('getName')->willReturn('Class');
        } else {
            $class->expects($this->once())->method('__toString')->willReturn('Class');
        }

        $param1->expects($this->once())->method('getName')->willReturn('param1');
        $param1->expects($this->once())->method('getType')->willReturn($class);

        $param2->expects($this->once())->method('getName')->willReturn('param2');
        $param2->expects($this->once())->method('getType')->willReturn(null);
        $param2->expects($this->once())->method('isDefaultValueAvailable')->willReturn(true);
        $param2->expects($this->once())->method('getDefaultValue')->willReturn('value2');

        $param3->expects($this->once())->method('getName')->willReturn('param3');

        $method->expects($this->once())->method('getParameters')->willReturn([$param1, $param2, $param3]);

        $container->expects($this->once())->method('has')->with($this->equalTo('Class'))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo('Class'))->willReturn('classObject');

        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $resolver->setLeagueContainer($container);

        $args = $resolver->reflectArguments($method, ['param3' => 'value3']);

        $this->assertSame('classObject', $args[0]);
        $this->assertSame('value2', $args[1]);
        $this->assertSame('value3', $args[2]);
    }

    /**
     * Asserts that the resolver throws an exception when reflection can't resolve a value.
     */
    public function testResolverThrowsExceptionWhenReflectionDoesNotResolve()
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $method = $this->getMockBuilder(ReflectionFunctionAbstract::class)->getMock();
        $param  = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();

        $param->expects($this->once())->method('getName')->willReturn('param1');
        $param->expects($this->once())->method('getType')->willReturn(null);
        $param->expects($this->once())->method('isDefaultValueAvailable')->willReturn(false);

        $method->expects($this->once())->method('getParameters')->willReturn([$param]);

        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $resolver->reflectArguments($method);
    }

    /**
     * Asserts that null is given for a type argument which can't be resolved.
     */
    public function testResolvesClassWithOptionalTypedArgument()
    {
        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $result = $resolver->reflectArguments((new ReflectionClass(Qux::class))->getConstructor());

        $this->assertSame([null], $result);
    }
}
