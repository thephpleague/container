---
layout: post
title: Introspection
sections:
    Introduction: introduction
    Listing Definitions: listing-definitions
    Listing Provider Services: listing-provider-services
    CLI Dump: cli-dump
---
## Introduction

Container provides a minimal introspection API for debugging and tooling purposes. These methods are available on the concrete `Container` class (not on `DefinitionContainerInterface` or PSR-11's `ContainerInterface`).

## Listing Definitions

`getDefinitionIds()` returns a list of all service IDs that have been explicitly registered via `add()` or `addShared()`:

~~~php
<?php

$container = new League\Container\Container();
$container->add(Acme\Foo::class);
$container->addShared(Acme\Bar::class);

$ids = $container->getDefinitionIds();
// ['Acme\Foo', 'Acme\Bar']
~~~

Services provided by lazy service providers are **not** included until the provider has been triggered. If a provider claims to provide `Acme\Baz::class` but has not yet been registered, it will not appear in `getDefinitionIds()`. Use `getServiceProviderIds()` to see what providers claim to provide.

## Listing Provider Services

`getServiceProviderIds()` returns all service IDs that registered service providers claim to provide, regardless of whether they have been triggered yet:

~~~php
<?php

$container = new League\Container\Container();
$container->addServiceProvider(new Acme\MyServiceProvider());

$providerIds = $container->getServiceProviderIds();
// ['Acme\Baz', 'Acme\Qux'] — whatever the provider declares
~~~

For this to work, your service providers must implement `getProvidedIds()`:

~~~php
<?php

class MyServiceProvider extends League\Container\ServiceProvider\AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, $this->getProvidedIds(), true);
    }

    public function getProvidedIds(): array
    {
        return [Acme\Baz::class, Acme\Qux::class];
    }

    public function register(): void
    {
        $this->getContainer()->add(Acme\Baz::class);
        $this->getContainer()->add(Acme\Qux::class);
    }
}
~~~

## CLI Dump

The `bin/container-compile` CLI tool includes a `--dump` flag that outputs a summary of all registered services using the compilation analyser:

~~~shell
vendor/bin/container-compile --input=bootstrap.php --dump
~~~

This outputs each service's ID, concrete type, shared status, and tags without writing any compiled output. Useful for verifying your container configuration before compilation.
