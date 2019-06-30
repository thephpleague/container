<?php declare(strict_types=1);

namespace League\Container\Test\ServiceProvider;

use League\Container\ServiceProvider\{AbstractServiceProvider, BootableServiceProviderInterface, ServiceProviderInterface};
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    /**
     * Return a service provider fake
     *
     * @return ServiceProviderInterface
     */
    protected function getServiceProvider(): ServiceProviderInterface
    {
        return new class extends AbstractServiceProvider implements BootableServiceProviderInterface {
            protected $provides = [
                'SomeService',
                'AnotherService'
            ];

            public function boot()
            {
                return true;
            }

            public function register()
            {
                $this->getLeagueContainer()->add('SomeService', function ($arg) {
                    return $arg;
                });

                return true;
            }
        };
    }

    /**
     * Asserts that the service provider correctly determines what it provides.
     */
    public function testServiceProviderCorrectlyDeterminesWhatIsProvided()
    {
        $provider = $this->getServiceProvider()->setIdentifier('something');

        $this->assertTrue($provider->provides('SomeService'));
        $this->assertTrue($provider->provides('AnotherService'));
        $this->assertFalse($provider->provides('NonService'));
    }
}
