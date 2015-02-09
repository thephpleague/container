<?php

namespace League\Container\Test\Asset;

use League\Container\ServiceProvider;

class ServiceProviderFake extends ServiceProvider
{
    protected $provides = [
        'test',
        'test.instance'
    ];

    public function register()
    {
        $this->getContainer()->add('test', 'League\Container\Test\Asset\Baz');
        $this->getContainer()->add('test.instance', new \stdClass);
    }
}
