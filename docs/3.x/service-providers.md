---
layout: post
title: Service Providers
sections:
    Introduction: introduction
    Usage: usage
    Bootable Service Providers: bootable-service-providers
---
## Introduction

Service providers give the benefit of organising your container definitions along with an increase in performance for larger applications as definitions registered within a service provider are lazily registered at the point where a service is retrieved.

## Usage

To build a service provider it is as simple as extending the base service provider and defining what you would like to register.

~~~ php
<?php declare(strict_types=1);

namespace Acme\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;

class SomeServiceProvider extends AbstractServiceProvider
{
    /**
     * The provided array is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        'key',
        'Some\Controller',
        'Some\Model',
        'Some\Request'
    ];

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {
        $this->getContainer()->add('key', 'value');

        $this->getContainer()
            ->add('Some\Controller')
             ->addArgument('Some\Request')
             ->addArgument('Some\Model')
        ;

        $this->getContainer()->add('Some\Request');
        $this->getContainer()->add('Some\Model');
    }
}
~~~

To register this service provider with the container simply pass an instance of your provider or a fully qualified class name to the `League\Container\Container::addServiceProvider` method.

~~~ php
<?php declare(strict_types=1);

$container = new League\Container\Container;

$container->addServiceProvider(new Acme\ServiceProvider\SomeServiceProvider);
$container->addServiceProvider('Acme\ServiceProvider\SomeServiceProvider');
~~~

The register method is not invoked until one of the aliases in the `$provides` array is requested by the container, therefore, when we want to retrieve one of the items provided by the service provider, it will not actually be registered until it is needed, this improves performance for larger applications as your dependency map grows.

## Bootable Service Providers

If there is functionality that needs to be run as the service provider is added to the container, for example, setting up inflectors, including config files etc, we can make the service provider bootable by implementing the `League\Container\ServiceProvider\BootableServiceProviderInterface`.

~~~ php
<?php

namespace Acme\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class SomeServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @var array
     */
    protected $provides = [
        // ...
    ];

    /**
     * In much the same way, this method has access to the container
     * itself and can interact with it however you wish, the difference
     * is that the boot method is invoked as soon as you register
     * the service provider with the container meaning that everything
     * in this method is eagerly loaded.
     *
     * If you wish to apply inflectors or register further service providers
     * from this one, it must be from a bootable service provider like
     * this one, otherwise they will be ignored.
     */
    public function boot()
    {
        $this->getContainer()
             ->inflector('SomeType')
             ->invokeMethod('someMethod', ['some_arg']);

    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        // ...
    }
}
~~~
