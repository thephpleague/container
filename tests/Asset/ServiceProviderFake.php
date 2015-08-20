<?php

namespace League\Container\Test\Asset;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class ServiceProviderFake extends AbstractServiceProvider implements BootableServiceProviderInterface
{
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
        $this->getContainer()->add('SomeService', function ($arg) {
            return $arg;
        });

        return true;
    }
}
