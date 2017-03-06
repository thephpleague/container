<?php

namespace League\Container\Test\Inflector;

use League\Container\Argument\RawArgument;
use League\Container\Inflector\InflectorAggregate;

class InflectorAggregateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the aggregate adds an inflector without a callback.
     */
    public function testAggregateAddsInflectorWithoutCallback()
    {
        $aggregate = new InflectorAggregate;
        $return = $aggregate->add('SomeType');

        $this->assertInstanceOf('League\Container\Inflector\Inflector', $return);

        $inflectors = (new \ReflectionClass($aggregate))->getProperty('inflectors');
        $inflectors->setAccessible(true);

        $this->assertArrayHasKey('SomeType', $inflectors->getValue($aggregate));
    }

    /**
     * Asserts that the aggregate inflects on an object without a callback.
     */
    public function testAggregateInflectsOnObjectWithoutCallback()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');
        $aggregate = (new InflectorAggregate)->setContainer($container);

        $aggregate->add('SomeType');

        $aggregate->add('stdClass')
                  ->setProperty('foo', new RawArgument('Foo'));

        $object = new \stdClass;

        $aggregate->inflect($object);

        $this->assertSame('Foo', $object->foo);
    }

    /**
     * Asserts that the aggregate inflects on an object with a callback.
     */
    public function testAggregateInflectsOnObjectWithCallback()
    {
        $container = $this->getMock('League\Container\ImmutableContainerInterface');
        $aggregate = (new InflectorAggregate)->setContainer($container);

        $aggregate->add('stdClass', function ($object) {
            $object->foo = 'Foo';
        });

        $object = new \stdClass;

        $aggregate->inflect($object);

        $this->assertSame('Foo', $object->foo);
    }
}
