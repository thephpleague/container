<?php

declare(strict_types=1);

use League\Container\Compiler\CodeGenerator;
use League\Container\Compiler\CompilationConfig;
use League\Container\Compiler\CompilationException;
use League\Container\Compiler\CompilationResult;
use League\Container\Compiler\Compiler;
use League\Container\Compiler\DefinitionAnalyser;
use League\Container\Container;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;

test('compile returns CompilationResult when given a Container instance', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class);

    $config = new CompilationConfig(namespace: 'Test', className: 'CompiledContainer');
    $result = (new Compiler())->compile($container, $config);

    expect($result)->toBeInstanceOf(CompilationResult::class)
        ->and($result->serviceCount)->toBe(2)
        ->and($result->fullyQualifiedClassName)->toBe('Test\CompiledContainer')
        ->and($result->phpSource)->toContain('class CompiledContainer')
        ->and($result->sourceHash)->toHaveLength(64);
});

test('compile returns CompilationResult when given a valid file path', function () {
    $bootstrapPath = __DIR__ . '/../Asset/Compiler/valid_bootstrap.php';
    $config = new CompilationConfig(namespace: 'Test', className: 'CompiledContainer');

    $result = (new Compiler())->compile($bootstrapPath, $config);

    expect($result)->toBeInstanceOf(CompilationResult::class)
        ->and($result->serviceCount)->toBe(2);
});

test('compile throws CompilationException when file path does not exist', function () {
    $config = new CompilationConfig();

    expect(fn() => (new Compiler())->compile('/nonexistent/path/bootstrap.php', $config))
        ->toThrow(CompilationException::class);
});

test('compile throws CompilationException when file path returns non-Container value', function () {
    $invalidBootstrap = sys_get_temp_dir() . '/invalid_bootstrap_' . uniqid() . '.php';
    file_put_contents($invalidBootstrap, '<?php return "not a container";');

    try {
        expect(fn() => (new Compiler())->compile($invalidBootstrap, new CompilationConfig()))
            ->toThrow(CompilationException::class);
    } finally {
        unlink($invalidBootstrap);
    }
});

test('compile throws CompilationException with all errors when analysis produces errors', function () {
    $container = new Container();
    $container->add('closure-service', static fn() => 'value');

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

test('compile result contains warnings from analysis when event listeners are registered', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->getEventDispatcher()->addListener('*', static fn() => null);

    $result = (new Compiler())->compile($container, new CompilationConfig());

    expect($result->warnings)->not->toBeEmpty();
});

test('compile result has no warnings when analysis produces none', function () {
    $container = new Container();
    $container->add(Bar::class);

    $result = (new Compiler())->compile($container, new CompilationConfig());

    expect($result->warnings)->toBe([]);
});

test('source hash is deterministic for the same container definitions', function () {
    $buildContainer = static function (): Container {
        $container = new Container();
        $container->add(Bar::class);
        $container->add(Foo::class);
        return $container;
    };

    $config = new CompilationConfig(namespace: 'App', className: 'CompiledContainer');

    $firstHash = (new Compiler())->compile($buildContainer(), $config)->sourceHash;
    $secondHash = (new Compiler())->compile($buildContainer(), $config)->sourceHash;

    expect($firstHash)->toBe($secondHash);
});

test('source hash differs when container definitions change', function () {
    $config = new CompilationConfig(namespace: 'App', className: 'CompiledContainer');

    $containerWithBar = new Container();
    $containerWithBar->add(Bar::class);

    $containerWithBarAndFoo = new Container();
    $containerWithBarAndFoo->add(Bar::class);
    $containerWithBarAndFoo->add(Foo::class);

    $hashOne = (new Compiler())->compile($containerWithBar, $config)->sourceHash;
    $hashTwo = (new Compiler())->compile($containerWithBarAndFoo, $config)->sourceHash;

    expect($hashOne)->not->toBe($hashTwo);
});

test('isStale returns false when compiled class hash matches current container', function () {
    $container = new Container();
    $container->add(Bar::class);

    $config = new CompilationConfig(namespace: 'App', className: 'CompiledContainer');
    $compiler = new Compiler();
    $result = $compiler->compile($container, $config);

    $outputPath = sys_get_temp_dir() . '/isStale_fresh_' . uniqid() . '.php';
    $result->writeTo($outputPath);
    require $outputPath;

    try {
        $freshContainer = new Container();
        $freshContainer->add(Bar::class);

        expect($compiler->isStale('App\CompiledContainer', $freshContainer))->toBeFalse();
    } finally {
        unlink($outputPath);
    }
});

test('isStale returns true when compiled class hash does not match current container', function () {
    $container = new Container();
    $container->add(Bar::class);

    $config = new CompilationConfig(namespace: 'App', className: 'CompiledContainerStale');
    $compiler = new Compiler();
    $result = $compiler->compile($container, $config);

    $outputPath = sys_get_temp_dir() . '/isStale_stale_' . uniqid() . '.php';
    $result->writeTo($outputPath);
    require $outputPath;

    try {
        $differentContainer = new Container();
        $differentContainer->add(Bar::class);
        $differentContainer->add(Foo::class);

        expect($compiler->isStale('App\CompiledContainerStale', $differentContainer))->toBeTrue();
    } finally {
        unlink($outputPath);
    }
});

test('isStale accepts a file path string instead of Container', function () {
    $container = new Container();
    $container->add(Bar::class);

    $config = new CompilationConfig(namespace: 'App', className: 'CompiledContainerFilePath');
    $compiler = new Compiler();
    $result = $compiler->compile($container, $config);

    $outputPath = sys_get_temp_dir() . '/isStale_filepath_' . uniqid() . '.php';
    $result->writeTo($outputPath);
    require $outputPath;

    $bootstrapPath = __DIR__ . '/../Asset/Compiler/valid_bootstrap.php';

    try {
        expect($compiler->isStale('App\CompiledContainerFilePath', $bootstrapPath))->toBeTrue();
    } finally {
        unlink($outputPath);
    }
});

test('isStale throws CompilationException when compiled class does not exist', function () {
    $container = new Container();
    $container->add(Bar::class);

    expect(fn() => (new Compiler())->isStale('NonExistent\CompiledContainer', $container))
        ->toThrow(CompilationException::class);
});
