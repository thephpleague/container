<?php

namespace League\Container\Test\Asset;

use League\Container\ServiceProvider;

class ServiceProviderFake extends ServiceProvider
{
    protected $provides = [
        'test',
        'test.instance',
        'test.variable',
    ];

    public function register()
    {
        $container = $this->getContainer();

        $container->add('test', 'League\Container\Test\Asset\Baz');
        $container->add('test.instance', new \stdClass);
        $container->add('test.variable', 'value');
    }
}
