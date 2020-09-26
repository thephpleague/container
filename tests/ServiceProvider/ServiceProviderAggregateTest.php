<?php

declare(strict_types=1);

namespace League\Container\Test\ServiceProvider;

use League\Container\{Container, ContainerAwareTrait};
use League\Container\Exception\ContainerException;
use League\Container\ServiceProvider\{
    AbstractServiceProvider,
    BootableServiceProviderInterface,
    ServiceProviderAggregate,
    ServiceProviderInterface
};
use PHPUnit\Framework\TestCase;

class ServiceProviderAggregateTest extends TestCase
{
    /**
     * Return a service provider fake
     *
     * @return ServiceProviderInterface
     */
    protected function getServiceProvider(): ServiceProviderInterface
    {
        return new class extends AbstractServiceProvider implements BootableServiceProviderInterface {
            use ContainerAwareTrait;

            protected $provides = [
                'SomeService',
                'AnotherService'
            ];

            public $booted     = 0;
            public $registered = 0;

            public function boot()
            {
                $this->booted++;
                return true;
            }

            public function register()
            {
                $this->registered++;

                $this->getContainer()->add('SomeService', function ($arg) {
                    return $arg;
                });

                return true;
            }
        };
    }

    /**
     * Asserts that the aggregate adds a class name service provider.
     */
    public function testAggregateAddsClassNameServiceProvider(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new ServiceProviderAggregate())->setContainer($container);

        $aggregate->add($this->getServiceProvider());

        self::assertTrue($aggregate->provides('SomeService'));
        self::assertTrue($aggregate->provides('AnotherService'));
    }

    /**
     * Asserts that an exception is thrown when adding a service provider that
     * does not exist.
     */
    public function testAggregateThrowsWhenCannotResolveServiceProvider(): void
    {
        $this->expectException(ContainerException::class);

        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new ServiceProviderAggregate())->setContainer($container);

        $aggregate->add('NonExistentClass');
    }

    /**
     * Asserts that an exception is thrown when attempting to invoke the register
     * method of a service provider that has not been provided.
     */
    public function testAggregateThrowsWhenRegisteringForServiceThatIsNotAdded(): void
    {
        $this->expectException(ContainerException::class);

        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new ServiceProviderAggregate())->setContainer($container);

        $aggregate->register('SomeService');
    }

    /**
     * Asserts that register method is only invoked once per service provider.
     */
    public function testAggregateInvokesCorrectRegisterMethodOnlyOnce(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new ServiceProviderAggregate())->setContainer($container);
        $provider  = $this->getServiceProvider();

        $aggregate->add($provider);

        $aggregate->register('SomeService');
        $aggregate->register('AnotherService');

        self::assertSame(1, $provider->registered);
    }


    /**
     * Asserts that adding a provider that has already been aggregated
     * will skip subsequent attempts to add the provider
     */
    public function testAggregateSkipsExistingProviders(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new ServiceProviderAggregate())->setContainer($container);
        $provider  = $this->getServiceProvider();

        $aggregate->add($provider);
        $aggregate->add($provider);

        // assert after adding provider multiple times, that it
        // was only aggregated and booted once
        self::assertSame(
            [$provider],
            iterator_to_array($aggregate->getIterator())
        );
        self::assertSame(1, $provider->booted);
    }
}
