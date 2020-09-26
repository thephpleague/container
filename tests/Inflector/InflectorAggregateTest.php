<?php

declare(strict_types=1);

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
    public function testAggregateAddsInflector(): void
    {
        $aggregate = new InflectorAggregate();
        $inflector = $aggregate->add('Some\Type');

        self::assertSame('Some\Type', $inflector->getType());
    }

    /**
     * Asserts that the aggregate adds and iterates multiple inflectors.
     */
    public function testAggregateAddsAndIteratesMultipleInflectors(): void
    {
        $aggregate  = new InflectorAggregate();
        $inflectors = [];

        for ($i = 0; $i < 10; $i++) {
            $inflectors[] = $aggregate->add('Some\Type' . $i);
        }

        foreach ($aggregate->getIterator() as $key => $inflector) {
            self::assertSame($inflectors[$key], $inflector);
        }
    }

    /**
     * Asserts that the aggregate iterates and inflects on an object.
     */
    public function testAggregateIteratesAndInflectsOnObject(): void
    {
        $aggregate      = new InflectorAggregate();
        $containerAware = $this->getMockBuilder(ContainerAwareInterface::class)->getMock();
        $container      = $this->getMockBuilder(Container::class)->getMock();

        $containerAware->expects(self::once())->method('setContainer')->with(self::equalTo($container));
        $aggregate->add(ContainerAwareInterface::class)->invokeMethod('setContainer', [$container]);
        $aggregate->add('Ignored\Type');
        $aggregate->setContainer($container);
        $aggregate->inflect($containerAware);
    }
}
