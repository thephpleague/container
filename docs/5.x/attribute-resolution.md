---
layout: post
title: Attribute Resolution
sections:
    Introduction: introduction
    Usage: usage
    Extending Attributes: extending-attributes
    Benefits: benefits
---
## Introduction

> Note: Attribute resolution is turned off by default but can be turned on by registering the `ReflectionContainer` as a container delegate. Read below and see the [documentation on delegate containers](/5.x/delegate-containers/).

Attribute resolution allows you to use PHP attributes to control how dependencies are resolved for your services. This provides a powerful and flexible alternative to auto wiring, enabling you to inject values, services, or even custom logic directly into your constructors or methods using attributes.

## Usage

The container provides built-in attributes for common resolution scenarios:

- `#[Inject('service.id')]` — Injects a value or service from the container by its ID.
- `#[Resolve('resolver.id', 'path.to.value')]` — Resolves a value from a service or array in the container, traversing the given path.
  - Method calls are supported in the path, allowing you to resolve complex values or configurations.
    - e.g. `#[Resolve('config', 'getDbConfig.host')]`

### Using `Inject`

~~~php
<?php 

namespace Acme;

use League\Container\Attribute\Inject;

class Bar
{
    public function hello(): string
    {
        return 'hello';
    }
}

class Foo
{
    public function __construct(
        #[Inject(Bar::class)] public readonly Bar $bar
    ) {}
}

$container = new League\Container\Container();
$container->delegate(new League\Container\ReflectionContainer());

$foo = $container->get(Foo::class);
echo $foo->bar->hello(); // 'hello'
~~~

### Using `Resolve`

~~~php
<?php 

namespace Acme;

use League\Container\Attribute\Resolve;

class Config {
    public readonly array $settings = [
        'db' => [
            'host' => 'localhost',
            'user' => 'root',
        ]
    ];
}

class Baz
{
    public function __construct(
        #[Resolve(Config::class, 'settings.db.host')] public readonly string $dbHost
    ) {}
}

$container = new League\Container\Container();
$container->delegate(new League\Container\ReflectionContainer());

$baz = $container->get(Baz::class);
// $baz->dbHost === 'localhost'
~~~

## Extending Attributes

You can create your own attributes to implement custom resolution logic. To access the container within your attribute, implement `ContainerAwareInterface` and use the `ContainerAwareTrait`. This gives you access to `$this->getContainer()`.

For example, to inject an environment variable:

~~~php
<?php 

namespace Acme;

use Attribute;
use League\Container\Attribute\AttributeInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Env implements AttributeInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(
        private readonly string $name
    ) {}

    public function resolve(): string
    {
        // You can access the container if needed via $this->getContainer()
        return getenv($this->name) ?: '';
    }
}

class NeedsSecret
{
    public function __construct(
        #[Env('MY_SECRET')] public readonly string $secret
    ) {}
}

putenv('MY_SECRET=super-secret-value');

$container = new League\Container\Container();
$container->delegate(new League\Container\ReflectionContainer());

$needsSecret = $container->get(NeedsSecret::class);
// $needsSecret->secret === 'super-secret-value'
~~~

## Benefits

Attribute resolution offers several advantages over auto wiring:

- **Fine-grained control:** Specify exactly how each dependency should be resolved, including primitives, services, or custom logic.
- **Extensibility:** Create your own attributes to integrate with configuration, environment, or any other source. Attributes can access the container for advanced scenarios.
- **Clarity:** Resolution logic is explicit and self-documenting in your code, making dependencies easier to understand and maintain.
- **Beyond constructor injection:** Unlike auto wiring, attribute resolution is not limited to objects or constructor arguments—you can resolve scalars, arrays, or any value your attribute logic supports.

While auto wiring is convenient for simple object graphs and constructor injection, attribute resolution is ideal for more complex scenarios where you need precise control over how dependencies are provided.
