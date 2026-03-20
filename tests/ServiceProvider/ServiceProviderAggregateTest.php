<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\Exception\ContainerException;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Container\ServiceProvider\ServiceProviderAggregate;
use League\Container\ServiceProvider\ServiceProviderInterface;

function createAggregateTestServiceProvider(): ServiceProviderInterface
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

test('aggregate adds class name service provider', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new ServiceProviderAggregate();
    $aggregate->setContainer($container);
    $aggregate->add(createAggregateTestServiceProvider());

    expect($aggregate->provides('SomeService'))->toBeTrue();
    expect($aggregate->provides('AnotherService'))->toBeTrue();
});

test('aggregate throws when registering for service that is not added', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new ServiceProviderAggregate();
    $aggregate->setContainer($container);

    expect(fn () => $aggregate->register('SomeService'))->toThrow(ContainerException::class);
});

test('aggregate invokes correct register method only once', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new ServiceProviderAggregate();
    $aggregate->setContainer($container);
    $provider = createAggregateTestServiceProvider();
    $aggregate->add($provider);
    $aggregate->register('SomeService');
    $aggregate->register('AnotherService');

    // @phpstan-ignore-next-line
    expect($provider->registered)->toBe(1);
});

test('register all registers every provider', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new ServiceProviderAggregate();
    $aggregate->setContainer($container);

    $firstProvider = createAggregateTestServiceProvider();

    $secondProvider = new class extends AbstractServiceProvider {
        public int $registered = 0;

        public function provides(string $id): bool
        {
            return $id === 'SecondService';
        }

        public function register(): void
        {
            $this->registered++;
        }
    };

    $aggregate->add($firstProvider);
    $aggregate->add($secondProvider);

    $aggregate->registerAll();

    // @phpstan-ignore-next-line
    expect($firstProvider->registered)->toBe(1);
    // @phpstan-ignore-next-line
    expect($secondProvider->registered)->toBe(1);
});

test('register all prevents double registration', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new ServiceProviderAggregate();
    $aggregate->setContainer($container);

    $provider = createAggregateTestServiceProvider();
    $aggregate->add($provider);

    $aggregate->registerAll();
    $aggregate->registerAll();

    // @phpstan-ignore-next-line
    expect($provider->registered)->toBe(1);
});

test('register all and register share double registration tracking', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new ServiceProviderAggregate();
    $aggregate->setContainer($container);

    $provider = createAggregateTestServiceProvider();
    $aggregate->add($provider);

    $aggregate->register('SomeService');
    $aggregate->registerAll();

    // @phpstan-ignore-next-line
    expect($provider->registered)->toBe(1);
});

test('aggregate skips existing providers', function () {
    $container = $this->getMockBuilder(Container::class)->getMock();
    $aggregate = new ServiceProviderAggregate();
    $aggregate->setContainer($container);
    $provider = createAggregateTestServiceProvider();
    $aggregate->add($provider);
    $aggregate->add($provider);

    expect(iterator_to_array($aggregate->getIterator()))->toBe([$provider]);

    // @phpstan-ignore-next-line
    expect($provider->booted)->toBe(1);
});
