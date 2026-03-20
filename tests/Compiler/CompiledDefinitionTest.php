<?php

declare(strict_types=1);

use League\Container\Compiler\CompiledDefinition;
use League\Container\Compiler\ConcreteType;

test('constructor stores all properties', function () {
    $definition = new CompiledDefinition(
        id: 'App\Service',
        concreteType: ConcreteType::ClassType,
        shared: true,
        resolvedArguments: ['new App\Dependency()'],
        methodCalls: [['method' => 'setLogger', 'arguments' => ['$logger']]],
        tags: ['service', 'loggable'],
        concreteClass: 'App\Service',
        factoryClass: null,
        factoryMethod: null,
    );

    expect($definition->id)->toBe('App\Service');
    expect($definition->concreteType)->toBe(ConcreteType::ClassType);
    expect($definition->shared)->toBeTrue();
    expect($definition->resolvedArguments)->toBe(['new App\Dependency()']);
    expect($definition->methodCalls)->toBe([['method' => 'setLogger', 'arguments' => ['$logger']]]);
    expect($definition->tags)->toBe(['service', 'loggable']);
    expect($definition->concreteClass)->toBe('App\Service');
    expect($definition->factoryClass)->toBeNull();
    expect($definition->factoryMethod)->toBeNull();
});

test('factory definition stores factory details', function () {
    $definition = new CompiledDefinition(
        id: 'App\Service',
        concreteType: ConcreteType::StaticCallable,
        shared: false,
        resolvedArguments: [],
        methodCalls: [],
        tags: [],
        concreteClass: null,
        factoryClass: 'App\ServiceFactory',
        factoryMethod: 'create',
    );

    expect($definition->concreteType)->toBe(ConcreteType::StaticCallable);
    expect($definition->shared)->toBeFalse();
    expect($definition->concreteClass)->toBeNull();
    expect($definition->factoryClass)->toBe('App\ServiceFactory');
    expect($definition->factoryMethod)->toBe('create');
});

test('properties are readonly', function () {
    $definition = new CompiledDefinition(
        id: 'App\Service',
        concreteType: ConcreteType::ClassType,
        shared: false,
        resolvedArguments: [],
        methodCalls: [],
        tags: [],
        concreteClass: 'App\Service',
        factoryClass: null,
        factoryMethod: null,
    );

    expect(fn() => $definition->id = 'mutated')->toThrow(Error::class);
});
