<?php

declare(strict_types=1);

namespace League\Container\Test\ServiceProvider;

use Exception;
use League\Container\Container;
use League\Container\Exception\ContainerException;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Container\ServiceProvider\ServiceProviderAggregate;
use League\Container\ServiceProvider\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;

class ServiceProviderAggregateTest extends TestCase
{
    protected function getServiceProvider(): ServiceProviderInterface
    {
        return new class extends AbstractServiceProvider implements BootableServiceProviderInterface {
            public int $booted = 0;
            public int $registered = 0;

            public function provides(string $id): bool
            {
                return in_array($id, [
                    'SomeService',
                    'AnotherService',
                ], true);
            }

            public function boot(): void
            {
                $this->booted++;
            }

            public function register(): void
            {
                $this->registered++;

                $this->getContainer()->add('SomeService', function ($arg) {
                    return $arg;
                });
            }
        };
    }

    public function testAggregateAddsClassNameServiceProvider(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = new ServiceProviderAggregate();
        $aggregate->setContainer($container);
        $aggregate->add($this->getServiceProvider());
        $this->assertTrue($aggregate->provides('SomeService'));
        $this->assertTrue($aggregate->provides('AnotherService'));
    }

    public function testAggregateThrowsWhenRegisteringForServiceThatIsNotAdded(): void
    {
        $this->expectException(ContainerException::class);
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = new ServiceProviderAggregate();
        $aggregate->setContainer($container);
        $aggregate->register('SomeService');
    }

    public function testAggregateInvokesCorrectRegisterMethodOnlyOnce(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = new ServiceProviderAggregate();
        $aggregate->setContainer($container);
        $provider = $this->getServiceProvider();
        $aggregate->add($provider);
        $aggregate->register('SomeService');
        $aggregate->register('AnotherService');
        // @phpstan-ignore-next-line
        $this->assertSame(1, $provider->registered);
    }

    /**
     * @throws Exception
     */
    public function testAggregateSkipsExistingProviders(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = new ServiceProviderAggregate();
        $aggregate->setContainer($container);
        $provider = $this->getServiceProvider();
        $aggregate->add($provider);
        $aggregate->add($provider);

        // assert after adding provider multiple times, that it
        // was only aggregated and booted once
        $this->assertSame(
            [$provider],
            iterator_to_array($aggregate->getIterator())
        );

        // @phpstan-ignore-next-line
        $this->assertSame(1, $provider->booted);
    }
}
