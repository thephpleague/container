<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\ContainerFactory;
use League\Container\Exception\ContainerException;
use League\Container\Test\Asset\Compiler\StubCompiledContainer;
use Psr\Container\ContainerInterface;

test('returns compiled container when compiled class exists and useCompiled is true', function () {
    $result = ContainerFactory::create(
        compiledClass: StubCompiledContainer::class,
        bootstrap: __DIR__ . '/Asset/Compiler/valid_bootstrap.php',
        useCompiled: true,
    );

    expect($result)->toBeInstanceOf(StubCompiledContainer::class);
});

test('falls back to bootstrap file when compiled class does not exist', function () {
    $result = ContainerFactory::create(
        compiledClass: 'NonExistent\CompiledContainer',
        bootstrap: __DIR__ . '/Asset/Compiler/valid_bootstrap.php',
        useCompiled: true,
    );

    expect($result)->toBeInstanceOf(Container::class);
});

test('falls back to bootstrap file when useCompiled is false even if compiled class exists', function () {
    $result = ContainerFactory::create(
        compiledClass: StubCompiledContainer::class,
        bootstrap: __DIR__ . '/Asset/Compiler/valid_bootstrap.php',
        useCompiled: false,
    );

    expect($result)->toBeInstanceOf(Container::class);
});

test('accepts a callable for bootstrap', function () {
    $result = ContainerFactory::create(
        compiledClass: 'NonExistent\CompiledContainer',
        bootstrap: static fn(): ContainerInterface => new Container(),
        useCompiled: true,
    );

    expect($result)->toBeInstanceOf(Container::class);
});

test('falls back to bootstrap callable when useCompiled is false even if compiled class exists', function () {
    $callableWasCalled = false;

    $result = ContainerFactory::create(
        compiledClass: StubCompiledContainer::class,
        bootstrap: static function () use (&$callableWasCalled): ContainerInterface {
            $callableWasCalled = true;
            return new Container();
        },
        useCompiled: false,
    );

    expect($result)->toBeInstanceOf(Container::class)
        ->and($callableWasCalled)->toBeTrue();
});

test('throws ContainerException when bootstrap file returns non-ContainerInterface value', function () {
    $invalidBootstrap = sys_get_temp_dir() . '/invalid_factory_bootstrap_' . uniqid() . '.php';
    file_put_contents($invalidBootstrap, '<?php return "not a container";');

    try {
        expect(fn() => ContainerFactory::create(
            compiledClass: 'NonExistent\CompiledContainer',
            bootstrap: $invalidBootstrap,
        ))->toThrow(ContainerException::class);
    } finally {
        unlink($invalidBootstrap);
    }
});

test('throws ContainerException when bootstrap callable returns non-ContainerInterface value', function () {
    expect(fn() => ContainerFactory::create(
        compiledClass: 'NonExistent\CompiledContainer',
        bootstrap: static fn(): string => 'not a container',
    ))->toThrow(ContainerException::class);
});

test('throws ContainerException when compiled class does not implement ContainerInterface', function () {
    expect(fn() => ContainerFactory::create(
        compiledClass: stdClass::class,
        bootstrap: __DIR__ . '/Asset/Compiler/valid_bootstrap.php',
        useCompiled: true,
    ))->toThrow(ContainerException::class);
});

test('throws ContainerException when bootstrap file path does not exist', function () {
    expect(fn() => ContainerFactory::create(
        compiledClass: 'NonExistent\CompiledContainer',
        bootstrap: '/nonexistent/path/bootstrap.php',
    ))->toThrow(ContainerException::class);
});
