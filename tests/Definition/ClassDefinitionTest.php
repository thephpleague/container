<?php

namespace League\Container\Test\Definition;

use League\Container\Argument\RawArgument;
use League\Container\Definition\ClassDefinition;
use League\Container\Test\Asset;

class ClassDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the class definition sets expected arguments.
     */
    public function testClassDefinitionSetsExpectedArguments()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');
        $definition = (new ClassDefinition('foo', 'League\Container\Test\Asset\Foo'))->setContainer($container);

        $definition->withArgument('League\Container\Test\Asset\Bar');

        $definition->withArguments([
            'League\Container\Test\Asset\Baz',
            'League\Container\Test\Asset\Bart'
        ]);

        $args = (new \ReflectionClass($definition))->getProperty('arguments');
        $args->setAccessible(true);

        $this->assertSame($args->getValue($definition), [
            'League\Container\Test\Asset\Bar',
            'League\Container\Test\Asset\Baz',
            'League\Container\Test\Asset\Bart'
        ]);
    }

    /**
     * Asserts that the class definition sets expected method calls.
     */
    public function testClassDefinitionSetsExpectedMethodCalls()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');
        $definition = (new ClassDefinition('foo', 'League\Container\Test\Asset\Foo'))->setContainer($container);

        $definition->withMethodCall('method1', ['arg1']);

        $definition->withMethodCalls([
            'method2' => ['arg1'],
            'method3' => ['arg1']
        ]);

        $methods = (new \ReflectionClass($definition))->getProperty('methods');
        $methods->setAccessible(true);

        $this->assertSame($methods->getValue($definition), [
            ['method' => 'method1', 'arguments' => ['arg1']],
            ['method' => 'method2', 'arguments' => ['arg1']],
            ['method' => 'method3', 'arguments' => ['arg1']]
        ]);
    }

    /**
     * Asserts that a definition can build a class and inject class based dependencies.
     */
    public function testDefinitionBuildsClassAndResolvesClassAliasedArguments()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(new Asset\Bar));

        $definition = (new ClassDefinition('foo', 'League\Container\Test\Asset\Foo'))->setContainer($container);

        $definition->withArgument('League\Container\Test\Asset\Bar');

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }

    /**
     * Asserts that a definition can build a class and inject scalar dependencies as raw argument.
     */
    public function testDefinitionBuildsClassAndInjectsScalarDependenciesAsRawArgument()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $definition = (new ClassDefinition('foo', 'League\Container\Test\Asset\FooWithScalarDependency'))->setContainer($container);

        $definition->withArgument(new RawArgument('some_string'))
                   ->withArgument(new RawArgument(['arr_with_key']))
                   ->withArgument(new RawArgument(42))
                   ->withArgument(new RawArgument(false));

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\FooWithScalarDependency', $foo);
        $this->assertSame('some_string', $foo->stringVal);
        $this->assertSame(['arr_with_key'], $foo->arrayVal);
        $this->assertSame(42, $foo->integerVal);
        $this->assertFalse($foo->booleanVal);
    }

    /**
     * Asserts that a definition can build a class and inject scalar dependencies as raw argument.
     */
    public function testDefinitionBuildsClassAndInjectsScalarDependenciesAsRawArgumentFromContainer()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->method('has')->will($this->returnValueMap([
            ['string', true],
            ['array', true],
            ['answer', true],
            ['false', true],
            ['null', true],
        ]));

        $container->method('get')->will($this->returnValueMap([
            ['string', new RawArgument('some_string')],
            ['array', new RawArgument(['arr_with_key'])],
            ['answer', new RawArgument(42)],
            ['false', new RawArgument(false)],
            ['null', new RawArgument(null)],
        ]));

        $definition = (new ClassDefinition('foo', 'League\Container\Test\Asset\FooWithScalarResolvedDependency'))->setContainer($container);

        $definition->withArgument('string')
                   ->withArgument('array')
                   ->withArgument('answer')
                   ->withArgument('false')
                   ->withArgument('null');

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\FooWithScalarResolvedDependency', $foo);
        $this->assertSame('some_string', $foo->stringVal);
        $this->assertSame(['arr_with_key'], $foo->arrayVal);
        $this->assertSame(42, $foo->integerVal);
        $this->assertFalse($foo->booleanVal);
        $this->assertSame(null, $foo->nullVal);
    }

    /**
     * Asserts that a definition can build a class and inject scalar dependencies.
     */
    public function testDefinitionBuildsClassAndInjectsScalarDependencies()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->once())->method('has')->with($this->equalTo('some_string'))->will($this->returnValue(false));

        $definition = (new ClassDefinition('foo', 'League\Container\Test\Asset\FooWithScalarDependency'))->setContainer($container);

        $definition->withArgument('some_string')
                   ->withArgument(['arr_with_key'])
                   ->withArgument(42)
                   ->withArgument(false);

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\FooWithScalarDependency', $foo);
        $this->assertSame('some_string', $foo->stringVal);
        $this->assertSame(['arr_with_key'], $foo->arrayVal);
        $this->assertSame(42, $foo->integerVal);
        $this->assertFalse($foo->booleanVal);
    }

    /**
     * Asserts that a definition can build a class and invoke methods.
     */
    public function testDefinitionBuildsClassAndInvokesMethods()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(new Asset\Bar));

        $definition = (new ClassDefinition('foo', 'League\Container\Test\Asset\Foo'))->setContainer($container);

        $definition->withMethodCall('setBar', ['League\Container\Test\Asset\Bar']);

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }
}
