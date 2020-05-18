<?php declare(strict_types=1);

namespace League\Container\Test\Inflector;

use League\Container\ContainerAwareInterface;
use League\Container\Inflector\InflectorAggregate;
use PHPUnit\Framework\TestCase;
use League\Container\Container;

class InflectorAggregateTest extends TestCase
{
    /**
     * Asserts that the aggregate can add an inflector.
     */
    public function testAggregateAddsInflector()
    {
        $aggregate = new InflectorAggregate;
        $inflector = $aggregate->add('Some\Type');

        $this->assertSame('Some\Type', $inflector->getType());
    }

    /**
     * Asserts that the aggregate adds and iterates multiple inflectors.
     */
    public function testAggregateAddsAndIteratesMultipleInflectors()
    {
        $aggregate  = new InflectorAggregate;
        $inflectors = [];

        for ($i = 0; $i < 10; $i++) {
            $inflectors[] = $aggregate->add('Some\Type' . $i);
        }

        foreach ($aggregate->getIterator() as $key => $inflector) {
            $this->assertSame($inflectors[$key], $inflector);
        }
    }

    /**
     * Asserts that the aggregate iterates and inflects on an object.
     */
    public function testAggregateIteratesAndInflectsOnObject()
    {
        $aggregate      = new InflectorAggregate;
        $containerAware = $this->getMockBuilder(ContainerAwareInterface::class)->getMock();
        $container      = $this->getMockBuilder(Container::class)->getMock();

        $containerAware->expects($this->once())->method('setLeagueContainer')->with($this->equalTo($container));
        $aggregate->add(ContainerAwareInterface::class)->invokeMethod('setLeagueContainer', [$container]);
        $aggregate->add('Ignored\Type');

        $aggregate->setLeagueContainer($container);

        $aggregate->inflect($containerAware);
    }
}
