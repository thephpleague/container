<?php

namespace League\Container\Test;

use League\Container\ReflectionContainer;

class ReflectionContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that ReflectionContainer claims it has an item if a class exists for the alias.
     */
    public function testHasReturnsTrueIfClassExists()
    {
        $container = new ReflectionContainer();

        $this->assertTrue($container->has('League\Container\Test\Asset\Bar'));
    }

    /**
     * Asserts that ReflectionContainer denies it has an item if a class does not exist for the alias.
     */
    public function testHasReturnsFalseIfClassDoesNotExist()
    {
        $container = new ReflectionContainer();

        $this->assertFalse($container->has('Foo\Bar\Baz'));
    }

    /**
     * Asserts that ReflectionContainer instantiates a class that does not have a constructor.
     */
    public function testGetInstantiatesClassWithoutConstructor()
    {
        $classWithoutConstructor = 'League\Container\Test\Asset\Bar';

        $container = new ReflectionContainer();

        $this->assertInstanceOf($classWithoutConstructor, $container->get($classWithoutConstructor));
    }
}
