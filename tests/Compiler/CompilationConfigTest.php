<?php

declare(strict_types=1);

use League\Container\Compiler\CompilationConfig;

test('default values are used when no arguments provided', function () {
    $config = new CompilationConfig();

    expect($config->namespace)->toBe('');
    expect($config->className)->toBe('CompiledContainer');
});

test('custom values override defaults', function () {
    $config = new CompilationConfig(
        namespace: 'App\Generated',
        className: 'MyContainer',
    );

    expect($config->namespace)->toBe('App\Generated');
    expect($config->className)->toBe('MyContainer');
});

test('properties are readonly', function () {
    $config = new CompilationConfig();

    expect(fn () => $config->className = 'mutated')->toThrow(Error::class);
});
