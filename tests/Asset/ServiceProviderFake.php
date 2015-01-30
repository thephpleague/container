<?php

namespace League\Container\Test\Asset;

use League\Container\ServiceProvider;

class ServiceProviderFake extends ServiceProvider
{
    public function resolve()
    {
        return new \stdClass;
    }
}
