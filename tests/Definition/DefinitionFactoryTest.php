<?php

namespace League\Container\Test\Definition;

use League\Container\Definition\DefinitionFactory;
use League\Container\Test\Asset;

class DefinitionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the correct definition is returned by the factory based on arguments passed.
     */
    public function testFactoryReturnsCorrectDefinitionBasedOnArguments()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');
        $factory   = (new DefinitionFactory)->setContainer($container);

        // existing class names
        $this->assertInstanceOf(
            'League\Container\Definition\ClassDefinition',
            $factory->getDefinition('foo', 'League\Container\Test\Asset\Foo', $container)
        );

        $this->assertInstanceOf(
            'League\Container\Definition\ClassDefinition',
            $factory->getDefinition('foo', 'League\Container\Test\Asset\Bar', $container)
        );

        // callables
        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', function () {}, $container)
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', [new Asset\FooWithNamedConstructor, 'namedConstructor'], $container)
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', ['League\Container\Test\Asset\FooWithNamedConstructor', 'namedConstructor'], $container)
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', ['League\Container\Test\Asset\FooWithNamedConstructor', 'staticNamedConstructor'], $container)
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', 'League\Container\Test\Asset\FooWithNamedConstructor::staticNamedConstructor', $container)
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', new Asset\FooWithNamedConstructor, $container)
        );

        // neither
        $str = 'some_string';
        $this->assertSame($str, $factory->getDefinition('foo', $str, $container)->build());

        $arr = ['some_array'];
        $this->assertSame($arr, $factory->getDefinition('foo', $arr, $container)->build());

        $i = 42;
        $this->assertSame($i, $factory->getDefinition('foo', $i, $container)->build());

        $bool = false;
        $this->assertSame($bool, $factory->getDefinition('foo', $bool, $container)->build());
    }
}
