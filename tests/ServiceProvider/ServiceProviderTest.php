<?php

declare(strict_types=1);

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;

function createTestServiceProvider(): ServiceProviderInterface
{
    return new class extends AbstractServiceProvider implements BootableServiceProviderInterface {
        public function provides(string $id): bool
        {
            return in_array($id, [
                'SomeService',
                'AnotherService',
            ], true);
        }

        public function boot(): void
        {
        }

        public function register(): void
        {
            $this->getContainer()->add('SomeService', function ($arg) {
                return $arg;
            });
        }
    };
}

test('service provider correctly determines what is provided', function () {
    $provider = createTestServiceProvider()->setIdentifier('something');

    expect($provider->provides('SomeService'))->toBeTrue();
    expect($provider->provides('AnotherService'))->toBeTrue();
    expect($provider->provides('NonService'))->toBeFalse();
});
