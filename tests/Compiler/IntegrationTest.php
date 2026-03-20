<?php

declare(strict_types=1);

use League\Container\Argument\DefaultValueArgument;
use League\Container\Argument\Literal\StringArgument;
use League\Container\Argument\ResolvableArgument;
use League\Container\Compiler\CompilationConfig;
use League\Container\Compiler\Compiler;
use League\Container\Container;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\BarInterface;
use League\Container\Test\Asset\Baz;
use League\Container\Test\Asset\Compiler\BarFactory;
use League\Container\Test\Asset\Compiler\ServiceWithTransitiveDeps;
use League\Container\Test\Asset\Foo;
use League\Container\Test\Asset\FooWithDefaultScalar;
use League\Container\Test\Asset\FooWithRequiredDependency;
use Psr\Container\ContainerInterface;

function compileAndLoad(Container $container, string $className, string $namespace = 'IntegrationTest'): ContainerInterface
{
    $config = new CompilationConfig(namespace: $namespace, className: $className);
    $result = (new Compiler())->compile($container, $config);

    $outputPath = sys_get_temp_dir() . '/' . $className . '_' . uniqid() . '.php';
    $result->writeTo($outputPath);
    require $outputPath;
    @unlink($outputPath);

    $fqcn = $namespace . '\\' . $className;
    return new $fqcn();
}

test('compiled container resolves class definitions identically to dynamic container', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class);

    $compiled = compileAndLoad($container, 'IntClassDefinitions');

    expect($compiled->get(Bar::class))->toBeInstanceOf(Bar::class)
        ->and($compiled->get(Foo::class))->toBeInstanceOf(Foo::class);
});

test('compiled container resolves interface-to-class alias identically', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(BarInterface::class, Bar::class);

    $compiled = compileAndLoad($container, 'IntAlias');

    expect($compiled->get(BarInterface::class))->toBeInstanceOf(Bar::class);
});

test('compiled container resolves shared services as singletons', function () {
    $container = new Container();
    $container->addShared(Bar::class);

    $compiled = compileAndLoad($container, 'IntShared');

    $firstInstance = $compiled->get(Bar::class);
    $secondInstance = $compiled->get(Bar::class);

    expect($firstInstance)->toBe($secondInstance);
});

test('compiled container resolves non-shared services as new instances', function () {
    $container = new Container();
    $container->add(Bar::class);

    $compiled = compileAndLoad($container, 'IntNonShared');

    $firstInstance = $compiled->get(Bar::class);
    $secondInstance = $compiled->get(Bar::class);

    expect($firstInstance)->not->toBe($secondInstance);
});

test('compiled container resolves services with method calls', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class)
        ->addMethodCall('setBar', [new ResolvableArgument(Bar::class)]);

    $compiled = compileAndLoad($container, 'IntMethodCalls');

    $foo = $compiled->get(Foo::class);

    expect($foo)->toBeInstanceOf(Foo::class)
        ->and($foo->bar)->toBeInstanceOf(Bar::class);
});

test('compiled container resolves tagged services', function () {
    $container = new Container();
    $container->add(Bar::class)->addTag('my-tag');
    $container->add(Foo::class)->addTag('my-tag');

    $compiled = compileAndLoad($container, 'IntTagged');

    $tagged = $compiled->get('my-tag');

    expect($tagged)->toBeArray()
        ->and($tagged)->toHaveCount(2)
        ->and($tagged[0])->toBeInstanceOf(Bar::class)
        ->and($tagged[1])->toBeInstanceOf(Foo::class);
});

test('compiled container resolves static callable definitions', function () {
    $container = new Container();
    $container->add(Bar::class, [BarFactory::class, 'create']);

    $compiled = compileAndLoad($container, 'IntStaticCallable');

    expect($compiled->get(Bar::class))->toBeInstanceOf(Bar::class);
});

test('compiled container resolves literal scalar values', function () {
    $container = new Container();
    $container->add('config.name', new StringArgument('hello'));

    $compiled = compileAndLoad($container, 'IntLiteralScalar');

    expect($compiled->get('config.name'))->toBe('hello');
});

test('compiled container resolves autowired dependencies with ReflectionContainer', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(FooWithRequiredDependency::class);

    $compiled = compileAndLoad($container, 'IntAutowired');

    $instance = $compiled->get(FooWithRequiredDependency::class);

    expect($instance)->toBeInstanceOf(FooWithRequiredDependency::class)
        ->and($instance->bar)->toBeInstanceOf(Bar::class);
});

test('compiled container has() returns true for all compiled service IDs', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class);

    $compiled = compileAndLoad($container, 'IntHasTrue');

    expect($compiled->has(Bar::class))->toBeTrue()
        ->and($compiled->has(Foo::class))->toBeTrue()
        ->and($compiled->has('unknown.service'))->toBeFalse();
});

test('compiled container has() returns true for tag IDs', function () {
    $container = new Container();
    $container->add(Bar::class)->addTag('my-tag');
    $container->add(Foo::class)->addTag('my-tag');

    $compiled = compileAndLoad($container, 'IntHasTag');

    expect($compiled->has('my-tag'))->toBeTrue();
});

test('compiled container throws NotFoundException for unknown service IDs', function () {
    $container = new Container();
    $container->add(Bar::class);

    $compiled = compileAndLoad($container, 'IntNotFound');

    expect(fn() => $compiled->get('unknown.service'))->toThrow(NotFoundException::class);
});

test('compiled container resolves services with explicit constructor arguments', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class)->addArgument(new ResolvableArgument(Bar::class));

    $compiled = compileAndLoad($container, 'IntExplicitArgs');

    $foo = $compiled->get(Foo::class);

    expect($foo)->toBeInstanceOf(Foo::class)
        ->and($foo->bar)->toBeInstanceOf(Bar::class);
});

test('compiled container resolves services with default scalar constructor values via autowiring', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(FooWithDefaultScalar::class);

    $compiled = compileAndLoad($container, 'IntDefaultScalar');

    $instance = $compiled->get(FooWithDefaultScalar::class);

    expect($instance)->toBeInstanceOf(FooWithDefaultScalar::class)
        ->and($instance->name)->toBe('default');
});

test('compiled container resolves multi-tagged services by each tag independently', function () {
    $container = new Container();
    $container->add(Bar::class)->addTag('repository')->addTag('cacheable');
    $container->add(Foo::class)->addTag('cacheable');

    $compiled = compileAndLoad($container, 'IntMultiTag');

    $repositories = $compiled->get('repository');
    $cacheables = $compiled->get('cacheable');

    expect($repositories)->toBeArray()
        ->and($repositories)->toHaveCount(1)
        ->and($repositories[0])->toBeInstanceOf(Bar::class)
        ->and($cacheables)->toBeArray()
        ->and($cacheables)->toHaveCount(2)
        ->and($cacheables[0])->toBeInstanceOf(Bar::class)
        ->and($cacheables[1])->toBeInstanceOf(Foo::class);
});

test('compiled container resolves transitive autowired dependencies across three levels', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(ServiceWithTransitiveDeps::class);

    $compiled = compileAndLoad($container, 'IntTransitive');

    $instance = $compiled->get(ServiceWithTransitiveDeps::class);

    expect($instance)->toBeInstanceOf(ServiceWithTransitiveDeps::class)
        ->and($instance->dependency)->toBeInstanceOf(FooWithRequiredDependency::class)
        ->and($instance->dependency->bar)->toBeInstanceOf(Bar::class);
});

test('compiled container resolves DefaultValueArgument falling back to default when service is absent', function () {
    $container = new Container();
    $container->add(Baz::class)
        ->addArgument(new DefaultValueArgument(BarInterface::class, null));

    $compiled = compileAndLoad($container, 'IntDefaultValue');

    $instance = $compiled->get(Baz::class);

    expect($instance)->toBeInstanceOf(Baz::class);
});
