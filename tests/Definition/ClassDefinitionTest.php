<?php

namespace League\Container\Test\Definition;

use League\Container\Definition\ClassDefinition;

class ClassDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the class definition sets expected arguments.
     */
    public function testClassDefinitionSetsExpectedArguments()
    {
        $container  = $this->getMock('League\Container\ImmutableContainerInterface');
        $definition = new ClassDefinition('foo', 'League\Container\Test\Asset\Foo', $container);

        $definition->withArgument('League\Container\Test\Asset\Bar');

        $args = (new \ReflectionClass($definition))->getProperty('arguments');
        $args->setAccessible(true);

        $this->assertSame($args->getValue($definition), ['League\Container\Test\Asset\Bar']);
    }
}
