<?php

namespace League\Container\Test\Inflector;

use League\Container\Inflector\Inflector;
use League\Container\Test\Asset;

class InflectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the inflector sets expected method calls.
     */
    public function testInflectorSetsExpectedMethodCalls()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');
        $inflector = (new Inflector)->setContainer($container);

        $inflector->invokeMethod('method1', ['arg1']);

        $inflector->invokeMethods([
            'method2' => ['arg1'],
            'method3' => ['arg1']
        ]);

        $methods = (new \ReflectionClass($inflector))->getProperty('methods');
        $methods->setAccessible(true);

        $this->assertSame($methods->getValue($inflector), [
            'method1' => ['arg1'],
            'method2' => ['arg1'],
            'method3' => ['arg1']
        ]);
    }

    /**
     * Asserts that the inflector sets expected properties.
     */
    public function testInflectorSetsExpectedProperties()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');
        $inflector = (new Inflector)->setContainer($container);

        $inflector->setProperty('property1', 'value');

        $inflector->setProperties([
            'property2' => 'value',
            'property3' => 'value'
        ]);

        $properties = (new \ReflectionClass($inflector))->getProperty('properties');
        $properties->setAccessible(true);

        $this->assertSame($properties->getValue($inflector), [
            'property1' => 'value',
            'property2' => 'value',
            'property3' => 'value'
        ]);
    }

    /**
     * Asserts that the inflector will inflect on an object with properties.
     */
    public function testInflectorInflectsWithProperties()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');

        $bar = new Asset\Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo( 'League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo( 'League\Container\Test\Asset\Bar'))->will($this->returnValue($bar));

        $inflector = (new Inflector)->setContainer($container)
                                    ->setProperty('bar', 'League\Container\Test\Asset\Bar');

        $foo = new Asset\Foo;
        $inflector->inflect($foo);

        $this->assertSame($bar, $foo->bar);
    }

    /**
     * Asserts that the inflector will inflect on an object with method call.
     */
    public function testInflectorInflectsWithMethodCall()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');

        $bar = new Asset\Bar;

        $container->expects($this->once())->method('has')->with($this->equalTo( 'League\Container\Test\Asset\Bar'))->will($this->returnValue(true));
        $container->expects($this->once())->method('get')->with($this->equalTo( 'League\Container\Test\Asset\Bar'))->will($this->returnValue($bar));

        $inflector = (new Inflector)->setContainer($container)
                                    ->invokeMethod('setBar', ['League\Container\Test\Asset\Bar']);

        $foo = new Asset\Foo;
        $inflector->inflect($foo);

        $this->assertSame($bar, $foo->bar);
    }
}
