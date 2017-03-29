<?php declare(strict_types=1);

namespace League\Container\Test;

use League\Container\Argument\{ArgumentResolverInterface, ArgumentResolverTrait, RawArgument};
use League\Container\{ContainerAwareTrait, Exception\NotFoundException};
use PHPUnit\Framework\TestCase;
use Psr\Container\{ContainerInterface, NotFoundExceptionInterface};
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;

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

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $container->expects($this->at(0))->method('has')->with($this->equalTo('alias1'))->will($this->returnValue(true));
        $container->expects($this->at(1))->method('get')->with($this->equalTo('alias1'))->will($this->returnValue($resolver));
        $container->expects($this->at(2))->method('has')->with($this->equalTo('alias2'))->will($this->returnValue(false));

        $resolver->setContainer($container);

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

        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $container->expects($this->at(0))->method('has')->with($this->equalTo('alias1'))->will($this->returnValue(true));
        $container->expects($this->at(1))->method('get')->with($this->equalTo('alias1'))->will($this->returnValue(new RawArgument('value1')));

        $resolver->setContainer($container);

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
        $class     = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        $class->expects($this->once())->method('getName')->will($this->returnValue('Class'));

        $param1->expects($this->once())->method('getName')->will($this->returnValue('param1'));
        $param1->expects($this->once())->method('getClass')->will($this->returnValue($class));

        $param2->expects($this->once())->method('getName')->will($this->returnValue('param2'));
        $param2->expects($this->once())->method('getClass')->will($this->returnValue(null));
        $param2->expects($this->once())->method('isDefaultValueAvailable')->will($this->returnValue(true));
        $param2->expects($this->once())->method('getDefaultValue')->will($this->returnValue('value2'));

        $param3->expects($this->once())->method('getName')->will($this->returnValue('param3'));

        $method->expects($this->once())->method('getParameters')->will($this->returnValue([$param1, $param2, $param3]));

        $container->expects($this->at(0))->method('has')->with($this->equalTo('Class'))->will($this->returnValue(false));
        $container->expects($this->at(1))->method('has')->with($this->equalTo('value2'))->will($this->returnValue(false));
        $container->expects($this->at(2))->method('has')->with($this->equalTo('value3'))->will($this->returnValue(false));

        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $resolver->setContainer($container);

        $args = $resolver->reflectArguments($method, ['param3' => 'value3']);

        $this->assertSame('Class', $args[0]);
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

        $param->expects($this->once())->method('getName')->will($this->returnValue('param1'));
        $param->expects($this->once())->method('getClass')->will($this->returnValue(null));
        $param->expects($this->once())->method('isDefaultValueAvailable')->will($this->returnValue(false));

        $method->expects($this->once())->method('getParameters')->will($this->returnValue([$param]));

        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;
        };

        $args = $resolver->reflectArguments($method);
    }
}
