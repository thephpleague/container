<?php declare(strict_types=1);

namespace League\Container\Test\Inflector;

use League\Container\Inflector\Inflector;
use PHPUnit\Framework\TestCase;
use League\Container\Container;
use ReflectionClass;
use League\Container\Test\Asset\Bar;

class InflectorTest extends TestCase
{
    /**
     * Asserts that the inflector sets expected method calls.
     */
    public function testInflectorSetsExpectedMethodCalls()
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $inflector = (new Inflector('Type'))->setLeagueContainer($container);

        $inflector->invokeMethod('method1', ['arg1']);

        $inflector->invokeMethods([
            'method2' => ['arg1'],
            'method3' => ['arg1']
        ]);

        $methods = (new ReflectionClass($inflector))->getProperty('methods');
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
        $container = $this->getMockBuilder(Container::class)->getMock();
        $inflector = (new Inflector('Type'))->setLeagueContainer($container);

        $inflector->setProperty('property1', 'value');

        $inflector->setProperties([
            'property2' => 'value',
            'property3' => 'value'
        ]);

        $properties = (new ReflectionClass($inflector))->getProperty('properties');
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
        $container = $this->getMockBuilder(Container::class)->getMock();

        $bar = new class {
        };

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->willReturn($bar);

        $inflector = (new Inflector('Type'))
            ->setLeagueContainer($container)
            ->setProperty('bar', Bar::class)
        ;

        $foo = new class {
            public $bar;
        };

        $inflector->inflect($foo);

        $this->assertSame($bar, $foo->bar);
    }

    /**
     * Asserts that the inflector will inflect on an object with method call.
     */
    public function testInflectorInflectsWithMethodCall()
    {
        $container = $this->getMockBuilder(Container::class)->getMock();

        $bar = new class {
        };

        $container->expects($this->once())->method('has')->with($this->equalTo('League\Container\Test\Asset\Bar'))->willReturn(true);
        $container->expects($this->once())->method('get')->with($this->equalTo('League\Container\Test\Asset\Bar'))->willReturn($bar);

        $inflector = (new Inflector('Type'))
            ->setLeagueContainer($container)
            ->invokeMethod('setBar', ['League\Container\Test\Asset\Bar'])
        ;

        $foo = new class {
            public $bar;
            public function setBar($bar)
            {
                $this->bar = $bar;
            }
        };

        $inflector->inflect($foo);

        $this->assertSame($bar, $foo->bar);
    }

    /**
     * Asserts that the inflector will inflect on an object with a callback.
     */
    public function testInflectorInflectsWithCallback()
    {
        $foo = new class {
            public $bar;
            public function setBar($bar)
            {
                $this->bar = $bar;
            }
        };

        $bar = new class {
        };

        $inflector = new Inflector('Type', function ($object) use ($bar) {
            $object->setBar($bar);
        });

        $inflector->inflect($foo);

        $this->assertSame($bar, $foo->bar);
    }
}
