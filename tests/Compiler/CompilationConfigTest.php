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

    expect(fn() => $config->className = 'mutated')->toThrow(Error::class);
});

test('empty class name throws invalid argument exception', function () {
    expect(fn() => new CompilationConfig(className: ''))
        ->toThrow(InvalidArgumentException::class, 'not a valid PHP identifier');
});

test('class name with invalid characters throws invalid argument exception', function () {
    expect(fn() => new CompilationConfig(className: 'Evil { }'))
        ->toThrow(InvalidArgumentException::class, 'not a valid PHP identifier');
});

test('namespace with invalid characters throws invalid argument exception', function () {
    expect(fn() => new CompilationConfig(namespace: 'Not Valid!'))
        ->toThrow(InvalidArgumentException::class, 'not a valid PHP namespace');
});

test('valid namespace with multiple segments is accepted', function () {
    $config = new CompilationConfig(namespace: 'App\\Generated\\Container');

    expect($config->namespace)->toBe('App\\Generated\\Container');
});
