<?php

namespace League\Container\Test;

use League\Container\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the container can set and get a simple closure with args.
     */
    public function testSetsAndGetsSimplePrototypedClosure()
    {
        $container = new Container;

        $container->add('test', function ($arg) {
            return $arg;
        });

        $this->assertTrue($container->has('test'));

        $this->assertEquals($container->get('test', ['hello']), 'hello');
        $this->assertEquals($container->get('test', ['world']), 'world');
    }

    /**
     * Asserts that the container sets and gets an instance as shared.
     */
    public function testSetsAndGetInstanceAsShared()
    {
        $container = new Container;

        $class = new \stdClass;

        $container->add('test', $class);

        $this->assertTrue($container->has('test'));

        $this->assertSame($container->get('test'), $class);
    }

    /**
     * Asserts that the container sets and gets via a service provider.
     */
    public function testSetsAndGetsViaServiceProvider()
    {
        $container = new Container;

        $container->addServiceProvider(new Asset\ServiceProviderFake);

        $this->assertTrue($container->has('SomeService'));

        $this->assertEquals($container->get('SomeService', ['hello']), 'hello');
    }

    /**
     * Asserts that an exception is thrown when attempting to get service that
     * does not exist.
     */
    public function testThrowsWhenGettingUnmanagedService()
    {
        $this->setExpectedException('InvalidArgumentException');

        $container = new Container;

        $container->get('nothing');
    }
}
