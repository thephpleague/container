<?php

namespace League\Container\Test\Definition;

use League\Container\Definition\CallableDefinition;
use League\Container\Test\Asset;

class CallableDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that a definition will invoke a callable with defined class alias dependencies.
     */
    public function testDefinitionInvokesCallableWithDefinedClassAliasedArguments()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(new Asset\Bar));

        $definition = (new CallableDefinition('foo', function (Asset\Bar $bar) {
            return new Asset\Foo($bar);
        }))->setContainer($container);

        $definition->withArgument('League\Container\Test\Asset\Bar');

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }

    /**
     * Asserts that a definition will invoke a named constructor with defined class alias dependencies.
     */
    public function testDefinitionInvokesNamedConstructorWithDefinedClassAliasedArguments()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->at(0))->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->at(1))->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(new Asset\Bar));
        $container->expects($this->at(2))->method('has')->with($this->equalTo('League\Container\Test\Asset\FooWithNamedConstructor'))->will($this->returnValue(true));
        $container->expects($this->at(3))->method('get')->with($this->equalTo('League\Container\Test\Asset\FooWithNamedConstructor'))->will($this->returnValue(new Asset\FooWithNamedConstructor));

        $definition = (new CallableDefinition('foo', ['League\Container\Test\Asset\FooWithNamedConstructor', 'namedConstructor']))->setContainer($container);

        $definition->withArgument('League\Container\Test\Asset\Bar');

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }

    /**
     * Asserts that a definition will invoke a static named constructor with defined class alias dependencies.
     */
    public function testDefinitionInvokesStaticNamedConstructorWithDefinedClassAliasedArguments()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->at(0))->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->at(1))->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(new Asset\Bar));
        $container->expects($this->at(2))->method('has')->with($this->equalTo('League\Container\Test\Asset\FooWithNamedConstructor'))->will($this->returnValue(false));

        $definition = (new CallableDefinition('foo', ['League\Container\Test\Asset\FooWithNamedConstructor', 'staticNamedConstructor']))->setContainer($container);

        $definition->withArgument('League\Container\Test\Asset\Bar');

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }

    /**
     * Asserts that a definition will invoke a static named constructor with defined class alias dependencies via a string callable.
     */
    public function testDefinitionInvokesStaticNamedConstructorWithDefinedClassAliasedArgumentsViaStringCallable()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(new Asset\Bar));

        $definition = (new CallableDefinition('foo', 'League\Container\Test\Asset\FooWithNamedConstructor::staticNamedConstructor'))->setContainer($container);

        $definition->withArgument('League\Container\Test\Asset\Bar');

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }

    /**
     * Asserts that a definition will invoke an invokable class with defined class alias dependencies.
     */
    public function testDefinitionInvokesInvokableClassWithDefinedClassAliasedArguments()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->will($this->returnValue(new Asset\Bar));

        $definition = (new CallableDefinition('foo', new Asset\FooWithNamedConstructor))->setContainer($container);

        $definition->withArgument('League\Container\Test\Asset\Bar');

        $foo = $definition->build();

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
    }
}
