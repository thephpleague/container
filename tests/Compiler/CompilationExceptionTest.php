<?php

declare(strict_types=1);

use League\Container\Compiler\CompilationException;
use League\Container\Exception\ContainerException;

test('extends container exception', function () {
    $exception = new CompilationException([]);

    expect($exception)->toBeInstanceOf(ContainerException::class);
});

test('get errors returns all provided errors', function () {
    $errors = [
        [
            'serviceId' => 'App\Service',
            'errorType' => 'closure',
            'message' => 'Cannot compile a closure.',
            'suggestedFix' => 'Replace the closure with a factory class.',
        ],
        [
            'serviceId' => 'App\Broken',
            'errorType' => 'missing_class',
            'message' => 'Class "App\Broken" does not exist.',
            'suggestedFix' => 'Ensure the class is autoloadable.',
        ],
    ];

    $exception = new CompilationException($errors);

    expect($exception->getErrors())->toBe($errors);
});

test('message is built from errors when no message provided', function () {
    $errors = [
        [
            'serviceId' => 'App\Service',
            'errorType' => 'closure',
            'message' => 'Cannot compile a closure.',
            'suggestedFix' => 'Use a factory.',
        ],
    ];

    $exception = new CompilationException($errors);

    expect($exception->getMessage())->toContain('closure');
    expect($exception->getMessage())->toContain('App\Service');
    expect($exception->getMessage())->toContain('Cannot compile a closure.');
});

test('custom message overrides generated message', function () {
    $exception = new CompilationException([], 'Custom failure message.');

    expect($exception->getMessage())->toBe('Custom failure message.');
});

test('empty errors produces default message', function () {
    $exception = new CompilationException([]);

    expect($exception->getMessage())->toBe('Container compilation failed.');
});

test('multiple errors are each represented in message', function () {
    $errors = [
        [
            'serviceId' => 'App\First',
            'errorType' => 'closure',
            'message' => 'First error.',
            'suggestedFix' => '',
        ],
        [
            'serviceId' => 'App\Second',
            'errorType' => 'missing_class',
            'message' => 'Second error.',
            'suggestedFix' => '',
        ],
    ];

    $exception = new CompilationException($errors);

    expect($exception->getMessage())->toContain('App\First');
    expect($exception->getMessage())->toContain('App\Second');
});
