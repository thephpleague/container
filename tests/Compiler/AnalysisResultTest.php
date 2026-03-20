<?php

declare(strict_types=1);

use League\Container\Compiler\AnalysisResult;
use League\Container\Compiler\CompiledDefinition;
use League\Container\Compiler\ConcreteType;
use League\Container\Compiler\DependencyGraph;

test('constructor stores all properties', function () {
    $compiledDefinition = new CompiledDefinition(
        id: 'App\Service',
        concreteType: ConcreteType::ClassType,
        shared: true,
        resolvedArguments: [],
        methodCalls: [],
        tags: [],
        concreteClass: 'App\Service',
        factoryClass: null,
        factoryMethod: null,
    );

    $dependencyGraph = new DependencyGraph();
    $tagMap = ['tagged.service' => ['App\Service']];
    $errors = [
        [
            'serviceId' => 'App\Service',
            'errorType' => 'unresolvable',
            'message' => 'Cannot resolve dependency.',
            'suggestedFix' => 'Register the dependency explicitly.',
        ],
    ];
    $warnings = ['App\OptionalService is not registered.'];

    $result = new AnalysisResult(
        compiledDefinitions: [$compiledDefinition],
        dependencyGraph: $dependencyGraph,
        tagMap: $tagMap,
        errors: $errors,
        warnings: $warnings,
    );

    expect($result->compiledDefinitions)->toBe([$compiledDefinition]);
    expect($result->dependencyGraph)->toBe($dependencyGraph);
    expect($result->tagMap)->toBe($tagMap);
    expect($result->errors)->toBe($errors);
    expect($result->warnings)->toBe($warnings);
});

test('has errors returns false when errors array is empty', function () {
    $result = new AnalysisResult(
        compiledDefinitions: [],
        dependencyGraph: new DependencyGraph(),
        tagMap: [],
        errors: [],
        warnings: [],
    );

    expect($result->hasErrors())->toBeFalse();
});

test('has errors returns true when errors array is non-empty', function () {
    $result = new AnalysisResult(
        compiledDefinitions: [],
        dependencyGraph: new DependencyGraph(),
        tagMap: [],
        errors: [
            [
                'serviceId' => 'App\Service',
                'errorType' => 'unresolvable',
                'message' => 'Cannot resolve dependency.',
                'suggestedFix' => 'Register the dependency explicitly.',
            ],
        ],
        warnings: [],
    );

    expect($result->hasErrors())->toBeTrue();
});

test('has warnings returns false when warnings array is empty', function () {
    $result = new AnalysisResult(
        compiledDefinitions: [],
        dependencyGraph: new DependencyGraph(),
        tagMap: [],
        errors: [],
        warnings: [],
    );

    expect($result->hasWarnings())->toBeFalse();
});

test('has warnings returns true when warnings array is non-empty', function () {
    $result = new AnalysisResult(
        compiledDefinitions: [],
        dependencyGraph: new DependencyGraph(),
        tagMap: [],
        errors: [],
        warnings: ['App\OptionalService is not registered.'],
    );

    expect($result->hasWarnings())->toBeTrue();
});

test('properties are readonly', function () {
    $result = new AnalysisResult(
        compiledDefinitions: [],
        dependencyGraph: new DependencyGraph(),
        tagMap: [],
        errors: [],
        warnings: [],
    );

    expect(fn() => $result->compiledDefinitions = [])->toThrow(Error::class);
});
