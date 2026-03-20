<?php

declare(strict_types=1);

use League\Container\Compiler\CompilationConfig;
use League\Container\Compiler\CompilationException;
use League\Container\Compiler\Compiler;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\CycleA;
use League\Container\Test\Asset\CycleB;
use League\Container\Test\Asset\FooWithRequiredInterfaceDependency;

test('compilation fails with CompilationException when container has closure definitions', function () {
    $container = new Container();
    $container->add('service', fn() => 'value');

    expect(fn() => (new Compiler())->compile($container, new CompilationConfig()))
        ->toThrow(CompilationException::class);
});

test('compilation error for closure concrete has correct errorType', function () {
    $container = new Container();
    $container->add('service', fn() => 'value');

    $exception = null;
    try {
        (new Compiler())->compile($container, new CompilationConfig());
    } catch (CompilationException $e) {
        $exception = $e;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->getErrors())->not->toBeEmpty()
        ->and($exception->getErrors()[0]['errorType'])->toBe('closure_concrete');
});

test('compilation fails with CompilationException when container has circular dependencies', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(CycleA::class);
    $container->add(CycleB::class);

    expect(fn() => (new Compiler())->compile($container, new CompilationConfig()))
        ->toThrow(CompilationException::class);
});

test('compilation error for circular dependency has correct errorType', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(CycleA::class);
    $container->add(CycleB::class);

    $exception = null;
    try {
        (new Compiler())->compile($container, new CompilationConfig());
    } catch (CompilationException $e) {
        $exception = $e;
    }

    $errorTypes = array_column($exception->getErrors(), 'errorType');

    expect($exception)->not->toBeNull()
        ->and($errorTypes)->toContain('circular_dependency');
});

test('compilation fails with CompilationException when autowired service requires unbound interface', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(FooWithRequiredInterfaceDependency::class);

    expect(fn() => (new Compiler())->compile($container, new CompilationConfig()))
        ->toThrow(CompilationException::class);
});

test('compilation error for unbound interface dependency has correct errorType', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(FooWithRequiredInterfaceDependency::class);

    $exception = null;
    try {
        (new Compiler())->compile($container, new CompilationConfig());
    } catch (CompilationException $e) {
        $exception = $e;
    }

    $errorTypes = array_column($exception->getErrors(), 'errorType');

    expect($exception)->not->toBeNull()
        ->and($errorTypes)->toContain('unresolvable_interface_parameter');
});

test('CompilationException contains all errors when multiple closure problems exist', function () {
    $container = new Container();
    $container->add('service.one', fn() => 'value one');
    $container->add('service.two', fn() => 'value two');

    $exception = null;
    try {
        (new Compiler())->compile($container, new CompilationConfig());
    } catch (CompilationException $e) {
        $exception = $e;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->getErrors())->toHaveCount(2);
});
