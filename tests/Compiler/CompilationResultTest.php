<?php

declare(strict_types=1);

use League\Container\Compiler\CompilationResult;

test('constructor stores all properties', function () {
    $result = new CompilationResult(
        phpSource: '<?php class CompiledContainer {}',
        fullyQualifiedClassName: 'App\Generated\CompiledContainer',
        sourceHash: hash('sha256', 'test'),
        serviceCount: 42,
    );

    expect($result->phpSource)->toBe('<?php class CompiledContainer {}');
    expect($result->fullyQualifiedClassName)->toBe('App\Generated\CompiledContainer');
    expect($result->sourceHash)->toBe(hash('sha256', 'test'));
    expect($result->serviceCount)->toBe(42);
});

test('write to creates file at given path', function () {
    $targetPath = sys_get_temp_dir() . '/compilation_result_test_' . uniqid() . '.php';

    $result = new CompilationResult(
        phpSource: '<?php // compiled',
        fullyQualifiedClassName: 'CompiledContainer',
        sourceHash: hash('sha256', 'test'),
        serviceCount: 1,
    );

    $result->writeTo($targetPath);

    expect($targetPath)->toBeFile();
    expect(file_get_contents($targetPath))->toBe('<?php // compiled');

    unlink($targetPath);
});

test('write to performs atomic write with no temporary file left', function () {
    $targetPath = sys_get_temp_dir() . '/compilation_result_atomic_test_' . uniqid() . '.php';
    $directory = sys_get_temp_dir();

    $filesBefore = glob($directory . '/compiled_container_*.php.tmp');

    $result = new CompilationResult(
        phpSource: '<?php // atomic',
        fullyQualifiedClassName: 'CompiledContainer',
        sourceHash: hash('sha256', 'atomic'),
        serviceCount: 0,
    );

    $result->writeTo($targetPath);

    $filesAfter = glob($directory . '/compiled_container_*.php.tmp');

    expect($filesAfter)->toBe($filesBefore);

    unlink($targetPath);
});

test('properties are readonly', function () {
    $result = new CompilationResult(
        phpSource: '<?php',
        fullyQualifiedClassName: 'C',
        sourceHash: 'abc',
        serviceCount: 0,
    );

    expect(fn() => $result->phpSource = 'mutated')->toThrow(Error::class);
});
