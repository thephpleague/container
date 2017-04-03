---
layout: default
title: Service Providers
---

# Service Providers

Service providers give the benefit of organising your container definitions along with an increase in performance for larger applications as definitions registered within a service provider are lazily registered.

To build a service provider it is as simple as extending the base service provider and defining what you would like to register.

~~~ php
namespace Acme\ServiceProvider;

use League\Container\ServiceProvider;

class SomeServiceProvider extends ServiceProvider
{
    /**
     * This array allows the container to be aware of
     * what your service provider actually provides,
     * this should contain all alias names that
     * you plan to register with the container
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
     * that you need to
     */
    public function register()
    {
        $this->getContainer()['key'] = 'value';
        $this->getContainer()->add('Some\Controller')
             ->withArgument('Some\Request')
             ->withArgument('Some\Model');

        $this->getContainer()->add('Some\Request');
        $this->getContainer()->add('Some\Model');
    }
}
~~~

To register this service provider with the container simply pass an instance of your provider or a fully qualified class name to the `League\Container\Container::addServiceProvider` method.

~~~ php
$container->addServiceProvider(new Acme\ServiceProvider\SomeServiceProvider);
$container->addServiceProvider('Acme\ServiceProvider\SomeServiceProvider');
~~~

Now when you want to retrieve one of the items provided by the service provider, it will not actually be registered until it is needed.
