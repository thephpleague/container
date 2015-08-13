<?php

namespace League\Container\Test\ServiceProvider;

use League\Container\Test\Asset;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the service provider correctly determines what it provides.
     */
    public function testServiceProviderCorrectlyDeterminesWhatIsProvided()
    {
        $provider = new Asset\ServiceProviderFake;

        $this->assertTrue($provider->provides('SomeService'));
        $this->assertTrue($provider->provides('AnotherService'));
        $this->assertFalse($provider->provides('NonService'));

        $this->assertSame($provider->provides(), [
            'SomeService',
            'AnotherService'
        ]);
    }
}
