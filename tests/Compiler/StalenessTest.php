<?php

declare(strict_types=1);

use League\Container\Compiler\CompilationConfig;
use League\Container\Compiler\Compiler;
use League\Container\Container;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;

test('isStale returns false when container definitions match the compiled class', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class);

    $config = new CompilationConfig(namespace: 'StalenessTest', className: 'CompiledContainerFresh');
    $compiler = new Compiler();
    $result = $compiler->compile($container, $config);

    $outputPath = sys_get_temp_dir() . '/StalenessTestFresh_' . uniqid() . '.php';
    $result->writeTo($outputPath);
    require $outputPath;

    try {
        $equivalentContainer = new Container();
        $equivalentContainer->add(Bar::class);
        $equivalentContainer->add(Foo::class);

        expect($compiler->isStale('StalenessTest\\CompiledContainerFresh', $equivalentContainer))->toBeFalse();
    } finally {
        unlink($outputPath);
    }
});

test('isStale returns true when a new definition is added after compilation', function () {
    $container = new Container();
    $container->add(Bar::class);

    $config = new CompilationConfig(namespace: 'StalenessTest', className: 'CompiledContainerNewDef');
    $compiler = new Compiler();
    $result = $compiler->compile($container, $config);

    $outputPath = sys_get_temp_dir() . '/StalenessTestNewDef_' . uniqid() . '.php';
    $result->writeTo($outputPath);
    require $outputPath;

    try {
        $expandedContainer = new Container();
        $expandedContainer->add(Bar::class);
        $expandedContainer->add(Foo::class);

        expect($compiler->isStale('StalenessTest\\CompiledContainerNewDef', $expandedContainer))->toBeTrue();
    } finally {
        unlink($outputPath);
    }
});

test('isStale returns true when a definition is changed from non-shared to shared after compilation', function () {
    $container = new Container();
    $container->add(Bar::class);

    $config = new CompilationConfig(namespace: 'StalenessTest', className: 'CompiledContainerSharedChange');
    $compiler = new Compiler();
    $result = $compiler->compile($container, $config);

    $outputPath = sys_get_temp_dir() . '/StalenessTestSharedChange_' . uniqid() . '.php';
    $result->writeTo($outputPath);
    require $outputPath;

    try {
        $changedContainer = new Container();
        $changedContainer->addShared(Bar::class);

        expect($compiler->isStale('StalenessTest\\CompiledContainerSharedChange', $changedContainer))->toBeTrue();
    } finally {
        unlink($outputPath);
    }
});
