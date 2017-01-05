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
            $factory->getDefinition('foo', 'League\Container\Test\Asset\Foo')
        );

        $this->assertInstanceOf(
            'League\Container\Definition\ClassDefinition',
            $factory->getDefinition('foo', 'League\Container\Test\Asset\Bar')
        );

        // callables
        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', function () {})
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', [new Asset\FooWithNamedConstructor, 'namedConstructor'])
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', ['League\Container\Test\Asset\FooWithNamedConstructor', 'namedConstructor'])
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', ['League\Container\Test\Asset\FooWithNamedConstructor', 'staticNamedConstructor'])
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', 'League\Container\Test\Asset\FooWithNamedConstructor::staticNamedConstructor')
        );

        $this->assertInstanceOf(
            'League\Container\Definition\CallableDefinition',
            $factory->getDefinition('foo', new Asset\FooWithNamedConstructor)
        );

        // neither
        $str = 'some_string';
        $this->assertSame($str, $factory->getDefinition('foo', $str));

        $arr = ['some_array'];
        $this->assertSame($arr, $factory->getDefinition('foo', $arr));

        $i = 42;
        $this->assertSame($i, $factory->getDefinition('foo', $i));

        $bool = false;
        $this->assertSame($bool, $factory->getDefinition('foo', $bool));
    }
}
