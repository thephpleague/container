---
layout: post
title: Service Providers
sections:
    Introduction: introduction
    Usage: usage
    Bootable Service Providers: bootable-service-providers
---
## Introduction

Service providers give the benefit of organising your container definitions, as well as an increase in performance for larger applications, as definitions registered within a service provider are lazily registered at the point where a service is retrieved.

## Usage

To build a service provider, you need to extend the base service provider, providing `register` method, and a `provides` method that will return `true` or `false` when the container invokes it with a service name (this allows the container to know ahead of time what a service provider provides, allowing for lazy loading).

~~~ php
<?php 

declare(strict_types=1);

namespace Acme\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;

class SomeServiceProvider extends AbstractServiceProvider
{
    /**
     * The provides method is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     */
    public function provides(string $id): bool
    {
        $services = [
            'key',
            Some\Controller::class,
            Some\Model::class,
            Some\Request::class,
        ];
        
        return in_array($id, $services);
    }

    /**
     * The register method is where you define services
     * in the same way you would directly with the container.
     * A convenience getter for the container is provided, you
     * can invoke any of the methods you would when defining
     * services directly, but remember, any alias added to the
     * container here, when passed to the `provides` nethod
     * must return true, or it will be ignored by the container.
     */
    public function register(): void
    {
        $this->getContainer()->add('key', 'value');

        $this->getContainer()
            ->add(Some\Controller::class)
             ->addArgument(Some\Request::class)
             ->addArgument(Some\Model::class)
        ;

        $this->getContainer()->add(Some\Request::class);
        $this->getContainer()->add(Some\Model::class);
    }
}
~~~

To register this service provider with the container simply pass an instance of your provider to the `League\Container\Container::addServiceProvider` method.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container->addServiceProvider(new Acme\ServiceProvider\SomeServiceProvider);
~~~

The register method is not invoked until one of the aliases it `provides` is requested by the container, therefore, when we want to retrieve one of the items provided by the service provider, it will not actually be registered until it is needed, this improves performance for larger applications as your dependency map grows.

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
    public function boot(): void
    {
        $this->getContainer()
             ->inflector('SomeType')
             ->invokeMethod('someMethod', ['some_arg'])
         ;
    }
    
    public function provides(string $id): bool
    {
        // ...
    }
    
    public function register(): void
    {
        // ...
    }
}
~~~
