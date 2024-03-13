<?php

declare(strict_types=1);

namespace League\Container\Test\ServiceProvider;

use League\Container\ServiceProvider\{
    AbstractServiceProvider,
    BootableServiceProviderInterface,
    ServiceProviderInterface
};
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    protected function getServiceProvider(): ServiceProviderInterface
    {
        return new class extends AbstractServiceProvider implements BootableServiceProviderInterface
        {
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

    public function testServiceProviderCorrectlyDeterminesWhatIsProvided(): void
    {
        $provider = $this->getServiceProvider()->setIdentifier('something');
        $this->assertTrue($provider->provides('SomeService'));
        $this->assertTrue($provider->provides('AnotherService'));
        $this->assertFalse($provider->provides('NonService'));
    }
}
