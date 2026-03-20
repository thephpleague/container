---
layout: post
title: Compilation
sections:
    Introduction: introduction
    Quick Start: quick-start
    Prerequisites: prerequisites
    CLI Reference: cli-reference
    PHP API Reference: php-api-reference
    ContainerFactory: containerfactory
    Development and Production Workflow: development-and-production-workflow
    CI/CD Integration: cicd-integration
    What Gets Compiled: what-gets-compiled
    What Gets Rejected: what-gets-rejected
    Migration Guide: migration-guide
    Limitations: limitations
    Error Handling: error-handling
---

## Introduction

Container compilation is a feature that takes a bootstrapped, fully-configured container and compiles it into a standalone PHP class. The compiled container implements the PSR-11 `ContainerInterface` and skips all the overhead of definition resolution, reflection-based autowiring, and closure evaluation at runtime. The compiled class IS the container, with all service definitions baked directly into methods.

This is particularly valuable for production environments where you want maximum performance and minimal startup time. Your development workflow remains unchanged—you configure your container dynamically as usual—but when deploying to production, you compile that configuration into a static PHP class that has zero reflection overhead.

## Quick Start

Getting from zero to a compiled container takes three simple steps.

### Create a Bootstrap File

First, create a bootstrap file (e.g. `bootstrap/container.php`) that returns a fully-configured `League\Container\Container` instance. This is the container that will be compiled. All your service definitions go here.

~~~ php
<?php

use League\Container\Container;

$container = new Container();

$container->add(App\Database\Connection::class)
    ->addArgument('sqlite:memory')
;

$container->add(App\Repository\UserRepository::class)
    ->addArgument(App\Database\Connection::class)
;

$container->add(App\Service\UserService::class)
    ->addArgument(App\Repository\UserRepository::class)
;

return $container;
~~~

### Run the CLI Compiler

Execute the compiler binary, pointing it to your bootstrap file:

~~~ shell
vendor/bin/container-compile --input=bootstrap/container.php
~~~

This generates `CompiledContainer.php` in your current directory by default.

### Use ContainerFactory to Load It

In your application entry point, use `ContainerFactory` to switch between compiled and dynamic containers depending on your environment:

~~~ php
<?php

use League\Container\ContainerFactory;

$container = ContainerFactory::create(
    compiledClass: App\Infrastructure\CompiledContainer::class,
    bootstrap: __DIR__ . '/bootstrap/container.php',
    useCompiled: ($_ENV['APP_ENV'] ?? 'dev') === 'production',
);

$userService = $container->get(App\Service\UserService::class);
~~~

That's it. When `APP_ENV` is `production` and the compiled container exists, you get instant service resolution. When developing locally, you get the full dynamic container with all its flexibility.

## Prerequisites

Before attempting to compile your container, ensure the following conditions are met.

### Bootstrap File Must Return a Container

The file passed to `--input` must return a `League\Container\Container` instance. The compiler requires this to analyse and compile your definitions.

~~~ php
<?php

use League\Container\Container;

$container = new Container();

return $container;
~~~

### All Service Definitions Must Be Side-Effect Free

Definitions are analysed and compiled at compile time. They cannot perform side effects (such as writing to files, making HTTP requests, or logging) because those effects only happen during compilation, not at runtime. Register your service definitions with pure data and callable references only.

### No Anonymous Closures as Concrete Values

The compiler cannot serialise anonymous closures. If you currently use closures as concrete values or arguments, you must refactor them into named callables or factory classes.

Unsupported (will not compile):

~~~ php
<?php

$container->add('service', fn() => new Service());
$container->add(Foo::class)->addArgument(fn() => 'value');
~~~

Supported alternatives: a factory class method, a static method on the service class, or an invokable class.

~~~ php
<?php

$container->add('service', [ServiceFactory::class, 'create']);

$container->add('service', [Service::class, 'make']);

$container->add('service', ServiceFactory::class);
~~~

## CLI Reference

The container compiler is available at `vendor/bin/container-compile`.

### Usage

~~~ shell
vendor/bin/container-compile [options]
~~~

### Options

| Option | Required | Description | Default |
|---|---|---|---|
| `--input=FILE` | Yes | Path to bootstrap file returning a Container instance | N/A |
| `--output=FILE` | No | Output path for the compiled PHP file | `./CompiledContainer.php` |
| `--class=NAME` | No | Generated class name | `CompiledContainer` |
| `--namespace=NS` | No | PHP namespace for the generated class | (none) |
| `--check` | No | Check if compiled container is stale without writing | N/A |
| `--dry-run` | No | Validate and report compilation results without writing | N/A |
| `--help` | No | Show usage information | N/A |

### Exit Codes

- `0` - Compilation succeeded (or check mode found container is fresh)
- `1` - Compilation failed (or check mode found container is stale)
- `2` - Invalid command-line arguments

### Examples

#### Basic Compilation

~~~ shell
vendor/bin/container-compile --input=bootstrap/container.php
~~~

Generates `CompiledContainer.php` in the current directory.

#### With Custom Namespace and Class Name

~~~ shell
vendor/bin/container-compile \
    --input=bootstrap/container.php \
    --output=src/Infrastructure/CompiledContainer.php \
    --class=Container \
    --namespace=App\Infrastructure
~~~

Generates `src/Infrastructure/CompiledContainer.php` with fully qualified class name `App\Infrastructure\Container`.

#### Dry Run (Validate Without Writing)

~~~ shell
vendor/bin/container-compile \
    --input=bootstrap/container.php \
    --dry-run
~~~

Validates the container and reports compilation statistics without writing any files. Useful in CI pipelines to validate that your container is compilable before deployment.

#### Check Mode (For CI/CD)

~~~ shell
vendor/bin/container-compile \
    --input=bootstrap/container.php \
    --check
~~~

Checks if the compiled container is stale compared to the bootstrap file. Exits with 0 if fresh, 1 if stale. The compiled class must be autoloadable (via Composer or otherwise) for check mode to work, as it uses `class_exists()` to locate the class and read its `SOURCE_HASH` constant.

## PHP API Reference

You can also compile containers programmatically from PHP code.

### Basic Compilation

~~~ php
<?php

use League\Container\Compiler\Compiler;
use League\Container\Compiler\CompilationConfig;
use League\Container\Container;

$container = new Container();
$container->add(MyService::class);

$compiler = new Compiler();
$result = $compiler->compile($container, new CompilationConfig(
    namespace: 'App\Infrastructure',
    className: 'CompiledContainer',
));

$result->writeTo('/path/to/output.php');
~~~

### Compiler Class

The `League\Container\Compiler\Compiler` class provides the main compilation interface.

#### Methods

**`compile(Container|string $container, CompilationConfig $config): CompilationResult`**

Compiles a container into PHP source code. Accepts either a `Container` instance or a string path to a bootstrap file. Returns a `CompilationResult` value object.

**`isStale(string $compiledClass, Container|string $container): bool`**

Checks whether a previously-compiled container is stale. Compares the current container definitions against the source hash embedded in the compiled class. Returns `true` if recompilation is needed.

~~~ php
<?php

$compiler = new Compiler();
$isStale = $compiler->isStale(
    App\Infrastructure\CompiledContainer::class,
    __DIR__ . '/bootstrap/container.php'
);

if ($isStale) {
    $result = $compiler->compile($container, $config);
    $result->writeTo('/path/to/CompiledContainer.php');
}
~~~

### CompilationConfig

Value object holding compilation settings.

~~~ php
<?php

use League\Container\Compiler\CompilationConfig;

$config = new CompilationConfig(
    namespace: 'App\Infrastructure',
    className: 'CompiledContainer',
);
~~~

**Properties:**

- `namespace: string` - PHP namespace for the generated class (default: empty string)
- `className: string` - Class name (default: `CompiledContainer`)

Both values are validated to ensure they form valid PHP identifiers and namespaces.

### CompilationResult

Value object returned by `Compiler::compile()`.

**Properties:**

- `phpSource: string` - The generated PHP source code
- `fullyQualifiedClassName: string` - Fully qualified class name (e.g. `App\Infrastructure\CompiledContainer`)
- `sourceHash: string` - SHA-256 hash of the definition state (used for staleness detection)
- `serviceCount: int` - Number of services compiled
- `warnings: array` - List of warnings (e.g. non-compilable definitions)

**Methods:**

**`writeTo(string $path): void`**

Writes the compiled PHP source to a file. Uses atomic writing (temporary file + rename) to ensure data integrity.

~~~ php
<?php

$result->writeTo('/path/to/CompiledContainer.php');
~~~

### Error Handling

The compiler throws `League\Container\Compiler\CompilationException` when it encounters errors that prevent compilation. The exception contains structured error information.

~~~ php
<?php

use League\Container\Compiler\CompilationException;

try {
    $compiler = new Compiler();
    $result = $compiler->compile($container, $config);
} catch (CompilationException $exception) {
    $errors = $exception->getErrors();

    foreach ($errors as $error) {
        echo "Service: " . $error['serviceId'] . PHP_EOL;
        echo "Type: " . $error['errorType'] . PHP_EOL;
        echo "Message: " . $error['message'] . PHP_EOL;
        echo "Fix: " . $error['suggestedFix'] . PHP_EOL;
    }
}
~~~

## ContainerFactory

`League\Container\ContainerFactory` provides a convenient way to switch between compiled and dynamic containers based on your environment.

### Usage

~~~ php
<?php

use League\Container\ContainerFactory;

$container = ContainerFactory::create(
    compiledClass: App\Infrastructure\CompiledContainer::class,
    bootstrap: __DIR__ . '/bootstrap/container.php',
    useCompiled: $_ENV['APP_ENV'] === 'production',
);
~~~

### Method Signature

**`ContainerFactory::create(string $compiledClass, string|callable $bootstrap, bool $useCompiled = true): ContainerInterface`**

- `$compiledClass` - Fully qualified class name of your compiled container
- `$bootstrap` - Either a file path (string) or callable that returns a `ContainerInterface`
- `$useCompiled` - Whether to use the compiled container (if it exists)

### Behaviour

When `$useCompiled` is `true`:

- If the compiled class exists and is loadable, an instance is created and returned
- If the compiled class does not exist, the bootstrap file/callable is used instead
- This allows graceful fallback if compilation hasn't happened yet

When `$useCompiled` is `false`:

- The bootstrap file/callable is always used
- The compiled class is never loaded

### Examples

#### File-Based Bootstrap

~~~ php
<?php

use League\Container\ContainerFactory;

$container = ContainerFactory::create(
    compiledClass: App\Infrastructure\CompiledContainer::class,
    bootstrap: __DIR__ . '/bootstrap/container.php',
    useCompiled: true,
);
~~~

#### Callable Bootstrap

~~~ php
<?php

use League\Container\ContainerFactory;

$container = ContainerFactory::create(
    compiledClass: App\Infrastructure\CompiledContainer::class,
    bootstrap: fn() => new League\Container\Container(),
    useCompiled: true,
);
~~~

#### Environment-Aware Switching

~~~ php
<?php

use League\Container\ContainerFactory;

$container = ContainerFactory::create(
    compiledClass: App\Infrastructure\CompiledContainer::class,
    bootstrap: __DIR__ . '/bootstrap/container.php',
    useCompiled: match($_ENV['APP_ENV'] ?? 'dev') {
        'production' => true,
        'staging' => true,
        default => false,
    },
);
~~~

## Development and Production Workflow

A typical workflow looks like this:

### Development

During development, use the dynamic container. This gives you full flexibility: you can add services at runtime, use closures, test with mocks, and rely on events and listeners. Your bootstrap file (e.g. `config/container.php`) might look like:

~~~ php
<?php

use League\Container\Container;

$container = new Container();

$container->add(Database::class)
    ->addArgument($_ENV['DATABASE_URL'] ?? 'sqlite:memory')
;

$container->afterResolve(LoggableInterface::class, fn($service) => $service->setLogger($logger));

return $container;
~~~

Use `ContainerFactory` with `useCompiled: false`:

~~~ php
<?php

$container = ContainerFactory::create(
    compiledClass: App\Infrastructure\CompiledContainer::class,
    bootstrap: __DIR__ . '/config/container.php',
    useCompiled: false,
);
~~~

### Deployment

Before deploying to production:

1. Ensure all service definitions are compilable (no closures, side effects, etc.)
2. Run the compiler to generate the compiled class
3. Commit the compiled class to version control
4. Deploy with `useCompiled: true`

~~~ shell
vendor/bin/container-compile --input=bootstrap/container.php
git add src/Infrastructure/CompiledContainer.php
git commit -m "Recompile container for production deployment"
~~~

### Runtime Switching

In production, the application entry point remains the same:

~~~ php
<?php

$container = ContainerFactory::create(
    compiledClass: App\Infrastructure\CompiledContainer::class,
    bootstrap: __DIR__ . '/bootstrap/container.php',
    useCompiled: $_ENV['APP_ENV'] === 'production',
);

$app = $container->get(Application::class);
$app->run();
~~~

The compiled container will be used in production. If compilation hasn't happened yet, it gracefully falls back to the dynamic container.

## CI/CD Integration

Use the `--check` flag in your CI pipeline to detect when compiled containers become stale.

### Detecting Stale Compiled Containers

Create a CI step that checks if your compiled container is up to date with your bootstrap file:

~~~ shell
#!/bin/bash

vendor/bin/container-compile --input=bootstrap/container.php --check

if [ $? -eq 1 ]; then
    echo "ERROR: Compiled container is stale. Run:"
    echo "  vendor/bin/container-compile --input=bootstrap/container.php"
    exit 1
fi
~~~

This exits with code 1 if recompilation is needed, preventing stale deployments.

### Deploy Script

A complete deployment script might look like:

~~~ bash
#!/bin/bash

set -e

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running tests..."
vendor/bin/phpunit

echo "Compiling container..."
vendor/bin/container-compile \
    --input=bootstrap/container.php \
    --output=src/Infrastructure/CompiledContainer.php \
    --namespace=App\Infrastructure \
    --class=CompiledContainer

echo "Deployment ready"
~~~

### Source Hash Constant

The compiled container includes a `SOURCE_HASH` constant that holds a SHA-256 hash of your definition state. This hash is based on:

- All service definition IDs and their configuration
- Dependency graph structure
- Constructor arguments
- Method calls
- Tagged definitions
- The compiler version (`COMPILER_VERSION` constant)

The hash does NOT depend on file modification times. It captures the semantic state of your definitions, so reordering code or refactoring without changing configuration does not invalidate the hash. Upgrading the container library itself may also invalidate the hash if the compiler version changes.

## What Gets Compiled

This table shows which definition types are supported and how they compile:

| Definition Type | Compiled As |
|---|---|
| Class with explicit constructor arguments | `new ClassName($this->get(Dep::class), 'literal')` |
| Interface aliased to concrete class | `return $this->get(ConcreteClass::class)` |
| Class relying on autowiring | Reflected at compile time, baked as explicit `new` with resolved dependencies |
| Literal or scalar value | Serialised via `var_export()` in a method returning the value |
| Static method callable | `FactoryClass::methodName(...$resolvedArgs)` |
| Instance method callable | `$this->get(FactoryClass::class)->methodName(...)` |
| Invokable class registered by string | Dependencies resolved, invoked as `(new InvokableClass(...))->__invoke(...)` |
| Definition with method calls | Sequential method invocations after construction |
| Tagged services | Method returning array of `$this->get()` calls for each tagged service |

## What Gets Rejected

The compiler collects all errors and reports them together, rather than failing on the first error. Each error includes a suggested migration path.

### Anonymous Closures

**Error type:** `closure_concrete`

**Migration:** Replace with a named callable. This will not compile:

~~~ php
<?php

$container->add(Logger::class, fn() => new Logger());
~~~

Instead, create a factory class and reference it:

~~~ php
<?php

namespace App\Factory;

class LoggerFactory
{
    public static function create(): Logger
    {
        return new Logger();
    }
}

$container->add(Logger::class, [LoggerFactory::class, 'create']);
~~~

### Closure::fromCallable()

`Closure::fromCallable()` returns a `Closure` and is caught by the same `closure_concrete` check.

**Migration:** Use the callable array directly. This will not compile:

~~~ php
<?php

$container->add('handler', Closure::fromCallable([HandlerFactory::class, 'handle']));
~~~

Replace with the callable array:

~~~ php
<?php

$container->add('handler', [HandlerFactory::class, 'handle']);
~~~

### Object Instances as Concrete or Arguments

**Error type:** `object_concrete`

**Migration:** Register a factory that constructs the instance. This will not compile:

~~~ php
<?php

$instance = new Service();
$container->add('service', $instance);
~~~

Replace with a factory method reference:

~~~ php
<?php

$container->add('service', [ServiceFactory::class, 'getInstance']);
~~~

### Interfaces with No Binding

**Error type:** `unresolvable_interface_parameter`

**Migration:** Add an explicit binding for the interface. If `Controller` depends on `LoggerInterface` but nothing binds it, this will fail:

~~~ php
<?php

$container->add(Controller::class);
~~~

Add the missing binding:

~~~ php
<?php

$container->add(LoggerInterface::class, FileLogger::class);
$container->add(Controller::class);
~~~

### Circular Dependencies

**Error type:** `circular_dependency`

**Migration:** Break the cycle using an interface or factory. A direct circular dependency like this will not compile:

~~~ php
<?php

$container->add(ServiceA::class)->addArgument(ServiceB::class);
$container->add(ServiceB::class)->addArgument(ServiceA::class);
~~~

Introduce an interface to break the cycle:

~~~ php
<?php

interface ServiceInterface {}

class ServiceA implements ServiceInterface
{
    public function __construct(private ServiceB $serviceB) {}
}

class ServiceB
{
    public function __construct(private ServiceInterface $service) {}
}

$container->add(ServiceInterface::class, ServiceA::class);
$container->add(ServiceA::class);
$container->add(ServiceB::class);
~~~

## Migration Guide

If you have an existing container that currently uses dynamic features, here's how to prepare it for compilation.

### Closures to Named Callables

Replace all closure-based definitions with static or instance method callables. A closure-based definition like this will not compile:

~~~ php
<?php

$container->add(Mailer::class, function() {
    return new Mailer(
        smtpHost: $_ENV['SMTP_HOST'],
        smtpPort: (int)$_ENV['SMTP_PORT'],
    );
});
~~~

Extract the logic into a factory class and reference the method:

~~~ php
<?php

class MailerFactory
{
    public static function create(): Mailer
    {
        return new Mailer(
            smtpHost: $_ENV['SMTP_HOST'],
            smtpPort: (int)$_ENV['SMTP_PORT'],
        );
    }
}

$container->add(Mailer::class, [MailerFactory::class, 'create']);
~~~

### Event Listeners to Method Calls

The compiled container does not fire events. Replace `afterResolve()` listeners with `addMethodCall()` on definitions. An event-based approach like this will not work in the compiled container:

~~~ php
<?php

$container->afterResolve(LoggableInterface::class, function (object $service) use ($container) {
    $service->setLogger($container->get(Logger::class));
});
~~~

Replace with explicit method calls on the affected definitions:

~~~ php
<?php

$container->add(SomeService::class)
    ->addMethodCall('setLogger', [Logger::class])
;
~~~

### getNew() Usage

The compiled container implements the PSR-11 `ContainerInterface` only. It does not have a `getNew()` method. If you use `getNew()` to get non-shared instances, you must:

1. Change code that calls `getNew()` to use `get()` instead
2. Ensure those services are registered as non-shared (the default)

Code using `getNew()`:

~~~ php
<?php

$service1 = $container->getNew(Service::class);
$service2 = $container->getNew(Service::class);
~~~

Replace with `get()` on a non-shared definition (which is the default behaviour):

~~~ php
<?php

$container->add(Service::class);

$service1 = $container->get(Service::class);
$service2 = $container->get(Service::class);
assert($service1 !== $service2);
~~~

### Type Hints: DefinitionContainerInterface to ContainerInterface

If you type-hint against `DefinitionContainerInterface` in your services, you'll get a compilation error in the compiled container (it only implements `ContainerInterface`). Change your type hints from `DefinitionContainerInterface`:

~~~ php
<?php

use League\Container\DefinitionContainerInterface;

class MyService
{
    public function __construct(private DefinitionContainerInterface $container) {}
}
~~~

To `ContainerInterface`:

~~~ php
<?php

use Psr\Container\ContainerInterface;

class MyService
{
    public function __construct(private ContainerInterface $container) {}
}
~~~

### Non-ReflectionContainer Delegates

If you use custom delegates (via `delegate()`), they are not compiled. A setup using custom delegates:

~~~ php
<?php

$container->delegate(MyCustomLocator::class);
~~~

Replace by adding explicit definitions for each service that was resolved through the delegate:

~~~ php
<?php

$container->add(Service1::class, [MyCustomLocator::class, 'getService1']);
$container->add(Service2::class, [MyCustomLocator::class, 'getService2']);
~~~

## Limitations

The compiled container has important limitations you should be aware of:

### No Event Dispatch

The compiled container does not fire any events. All event-based functionality (`on()`, `afterResolve()`, `afterResolving()`) is ignored during compilation and will not work in the compiled container.

**Mitigation:** Use `addMethodCall()` to configure services instead.

### No Runtime Additions

You cannot call `add()`, `addShared()`, or any definition methods on a compiled container. All services must be defined at compile time.

**Mitigation:** If you need to add services at runtime, use the dynamic container instead.

### No getNew() Method

The compiled container only implements `Psr\Container\ContainerInterface`. It does not have the `getNew()` method from `DefinitionContainerInterface`.

**Mitigation:** Use `get()` and ensure non-shared services are registered as such.

### No Closure Support

Closures cannot be serialised. Any closure used as a concrete value, argument, or factory will cause a compilation error.

**Mitigation:** Refactor closures into factory classes or static methods.

### Non-ReflectionContainer Delegates Not Compiled

Custom delegates are not included in the compiled container. Only the `ReflectionContainer` delegate (used for autowiring) is compiled.

**Mitigation:** Add explicit definitions for services that would be resolved through custom delegates.

## Error Handling

When compilation fails, the compiler throws `League\Container\Compiler\CompilationException`. This exception contains structured error information and can be caught and handled programmatically.

### Exception Structure

Each error in the exception contains:

- `serviceId` - The ID of the service that caused the error
- `errorType` - Category of the error (e.g. `circular_dependency`, `closure_concrete`, `object_concrete`)
- `message` - Description of what went wrong
- `suggestedFix` - Recommendation for how to fix it

### Catching and Handling Errors

~~~ php
<?php

use League\Container\Compiler\CompilationException;
use League\Container\Compiler\Compiler;

try {
    $compiler = new Compiler();
    $result = $compiler->compile($container, $config);
} catch (CompilationException $exception) {
    $errors = $exception->getErrors();

    if (count($errors) === 0) {
        echo "Compilation failed: " . $exception->getMessage() . PHP_EOL;
    } else {
        echo count($errors) . " compilation error(s) found:" . PHP_EOL . PHP_EOL;

        foreach ($errors as $error) {
            echo "Service: {$error['serviceId']}" . PHP_EOL;
            echo "Error: {$error['errorType']}" . PHP_EOL;
            echo "Message: {$error['message']}" . PHP_EOL;
            echo "Fix: {$error['suggestedFix']}" . PHP_EOL . PHP_EOL;
        }
    }
}
~~~

The compiler collects all errors before throwing, so you can address multiple issues at once rather than fixing them one at a time.
