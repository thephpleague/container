<?php

namespace League\Container\Test\ServiceProvider;

use League\Container\ServiceProvider\ServiceProviderAggregate;

class ServiceProviderAggregateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the aggregate adds a class name service provider.
     */
    public function testAggregateAddsClassNameServiceProvider()
    {
        $container = $this->getMock('League\Container\ContainerInterface');
        $aggregate = (new ServiceProviderAggregate)->setContainer($container);

        $aggregate->add('League\Container\Test\Asset\ServiceProviderFake');

        $this->assertTrue($aggregate->provides('SomeService'));
        $this->assertTrue($aggregate->provides('AnotherService'));
    }

    /**
     * Asserts that an exception is thrown when adding a service provider that
     * does not exist.
     */
    public function testAggregateThrowsWhenCannotResolveServiceProvider()
    {
        $this->setExpectedException('InvalidArgumentException');

        $container = $this->getMock('League\Container\ContainerInterface');
        $aggregate = (new ServiceProviderAggregate)->setContainer($container);

        $aggregate->add('NonExistentClass');
    }

    /**
     * Asserts that an exception is thrown when attempting to invoke the register
     * method of a service provider that has not been provided.
     */
    public function testAggregateThrowsWhenRegisteringForServiceThatIsNotAdded()
    {
        $this->setExpectedException('InvalidArgumentException');

        $container = $this->getMock('League\Container\ContainerInterface');
        $aggregate = (new ServiceProviderAggregate)->setContainer($container);

        $aggregate->register('SomeService');
    }

    public function testAggregateInvokesCorrectRegisterMethodOnlyOnce()
    {
        $container = $this->getMock('League\Container\ContainerInterface');
        $aggregate = (new ServiceProviderAggregate)->setContainer($container);
        $provider  = $this->getMock('League\Container\Test\Asset\ServiceProviderFake');

        $provider->expects($this->once())->method('boot');
        $provider->expects($this->once())->method('register');
        $provider->expects($this->once())->method('provides')->will($this->returnValue(['SomeService', 'AnotherService']));


        $aggregate->add($provider);

        $aggregate->register('SomeService');
        $aggregate->register('AnotherService');
    }
}
