<?php

declare(strict_types=1);

use League\Container\Argument\Literal\ObjectArgument;
use League\Container\Argument\Literal\StringArgument;
use League\Container\Argument\LiteralArgument;
use League\Container\Argument\ResolvableArgument;
use League\Container\Compiler\CompiledDefinition;
use League\Container\Compiler\ConcreteType;
use League\Container\Compiler\DefinitionAnalyser;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\ApiService;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\BarInterface;
use League\Container\Test\Asset\CacheInterface;
use League\Container\Test\Asset\CycleA;
use League\Container\Test\Asset\CycleB;
use League\Container\Test\Asset\FileCache;
use League\Container\Test\Asset\Foo;
use League\Container\Test\Asset\FooCallable;
use League\Container\Test\Asset\FooWithAttr;
use League\Container\Test\Asset\FooWithDefaultScalar;
use League\Container\Test\Asset\FooWithIntersectionType;
use League\Container\Test\Asset\FooWithPrivateConstructor;
use League\Container\Test\Asset\FooWithRequiredDependency;
use League\Container\Test\Asset\FooWithRequiredInterfaceDependency;
use League\Container\Test\Asset\FooWithResolveAttr;
use League\Container\Test\Asset\FooWithUnionType;
use League\Container\Test\Asset\FooWithUnionTypeDefault;
use League\Container\Test\Asset\LogService;
use League\Container\Test\Asset\RedisCache;
use Psr\Container\ContainerInterface;

test('class definition with explicit resolvable argument produces ClassType with get expression', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class)->addArgument(new ResolvableArgument(Bar::class));

    $result = (new DefinitionAnalyser())->analyse($container);

    $fooDefinition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($fooDefinition->concreteType)->toBe(ConcreteType::ClassType)
        ->and($fooDefinition->concreteClass)->toBe(Foo::class)
        ->and($fooDefinition->resolvedArguments)->toHaveCount(1)
        ->and($fooDefinition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\Bar')");
});

test('class definition with resolvable argument adds edge to dependency graph', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class)->addArgument(new ResolvableArgument(Bar::class));

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->dependencyGraph->getDependencies(Foo::class))->toContain(Bar::class);
});

test('interface to class definition produces Alias type', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(BarInterface::class, Bar::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $aliasDefinition = findCompiledDefinition($result->compiledDefinitions, BarInterface::class);

    expect($aliasDefinition->concreteType)->toBe(ConcreteType::Alias)
        ->and($aliasDefinition->concreteClass)->toBe(Bar::class)
        ->and($aliasDefinition->factoryClass)->toBeNull();
});

test('shared service definition has shared flag set to true', function () {
    $container = new Container();
    $container->addShared(Foo::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $fooDefinition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($fooDefinition->shared)->toBeTrue();
});

test('non-shared service definition has shared flag set to false', function () {
    $container = new Container();
    $container->add(Foo::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $fooDefinition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($fooDefinition->shared)->toBeFalse();
});

test('literal argument definition produces Literal type', function () {
    $container = new Container();
    $container->add('config.name', new StringArgument('myapp'));

    $result = (new DefinitionAnalyser())->analyse($container);

    $literalDefinition = findCompiledDefinition($result->compiledDefinitions, 'config.name');

    expect($literalDefinition->concreteType)->toBe(ConcreteType::Literal);
});

test('boolean literal argument definition produces Literal type', function () {
    $container = new Container();
    $container->add('config.debug', new LiteralArgument(true));

    $result = (new DefinitionAnalyser())->analyse($container);

    $literalDefinition = findCompiledDefinition($result->compiledDefinitions, 'config.debug');

    expect($literalDefinition->concreteType)->toBe(ConcreteType::Literal);
});

test('static array callable definition produces StaticCallable type', function () {
    $container = new Container();
    $container->add(Foo::class, [Foo::class, 'staticSetBar']);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($definition->concreteType)->toBe(ConcreteType::StaticCallable)
        ->and($definition->factoryClass)->toBe(Foo::class)
        ->and($definition->factoryMethod)->toBe('staticSetBar')
        ->and($definition->concreteClass)->toBeNull();
});

test('instance method array callable definition produces InstanceCallable type', function () {
    $container = new Container();
    $container->add(Foo::class, [Foo::class, 'setBar']);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($definition->concreteType)->toBe(ConcreteType::InstanceCallable)
        ->and($definition->factoryClass)->toBe(Foo::class)
        ->and($definition->factoryMethod)->toBe('setBar')
        ->and($definition->concreteClass)->toBeNull();
});

test('invokable class definition produces Invokable type', function () {
    $container = new Container();
    $container->add(Foo::class, FooCallable::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($definition->concreteType)->toBe(ConcreteType::Invokable)
        ->and($definition->concreteClass)->toBe(FooCallable::class)
        ->and($definition->factoryClass)->toBeNull();
});

test('method calls are compiled and included in the definition', function () {
    $container = new Container();
    $container->add(Bar::class)->addMethodCall('setSomething', ['test-value']);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Bar::class);

    expect($definition->methodCalls)->toHaveCount(1)
        ->and($definition->methodCalls[0]['method'])->toBe('setSomething')
        ->and($definition->methodCalls[0]['arguments'])->toContain("'test-value'");
});

test('method calls with resolvable arguments add edges to dependency graph', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class)->addMethodCall('setBar', [new ResolvableArgument(Bar::class)]);

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->dependencyGraph->getDependencies(Foo::class))->toContain(Bar::class);
});

test('tagged services appear in tag map', function () {
    $container = new Container();
    $container->add(Bar::class)->addTag('repository');

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->tagMap)->toHaveKey('repository')
        ->and($result->tagMap['repository'])->toContain(Bar::class);
});

test('shared tag is excluded from tag map', function () {
    $container = new Container();
    $container->addShared(Bar::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->tagMap)->not->toHaveKey('shared');
});

test('shared tag is excluded from compiled definition tags', function () {
    $container = new Container();
    $container->addShared(Bar::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Bar::class);

    expect($definition->tags)->not->toContain('shared');
});

test('closure concrete produces error and is excluded from compiled definitions', function () {
    $container = new Container();
    $container->add('service', static fn() => new Foo());

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors[0]['errorType'])->toBe('closure_concrete')
        ->and($result->errors[0]['serviceId'])->toBe('service');

    expect($result->compiledDefinitions)->toBeEmpty();
});

test('closure concrete error includes suggested fix', function () {
    $container = new Container();
    $container->add('service', static fn() => new Foo());

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->errors[0]['suggestedFix'])->not->toBeEmpty();
});

test('object instance concrete produces error and is excluded from compiled definitions', function () {
    $container = new Container();
    $container->add('service', new ObjectArgument(new Bar()));

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors[0]['errorType'])->toBe('object_concrete')
        ->and($result->errors[0]['serviceId'])->toBe('service');

    expect($result->compiledDefinitions)->toBeEmpty();
});

test('object concrete error message names the object class', function () {
    $container = new Container();
    $container->add('service', new ObjectArgument(new Bar()));

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->errors[0]['message'])->toContain(Bar::class);
});

test('event listeners on dispatcher produce a warning', function () {
    $container = new Container();
    $container->add(Foo::class);
    $container->addListener('some.event', static fn() => null);

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->hasWarnings())->toBeTrue()
        ->and($result->warnings[0])->toContain('event listeners');
});

test('no event listeners produces no event warning', function () {
    $container = new Container();
    $container->add(Foo::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $eventWarnings = array_filter($result->warnings, static fn(string $w): bool => str_contains($w, 'event listeners'));

    expect($eventWarnings)->toBeEmpty();
});

test('tag name matching a service ID produces a collision warning', function () {
    $container = new Container();
    $container->add(Bar::class)->addTag(Bar::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->hasWarnings())->toBeTrue();

    $collisionWarnings = array_filter($result->warnings, static fn(string $w): bool => str_contains($w, 'collides'));

    expect($collisionWarnings)->not->toBeEmpty();
});

test('non-reflection delegate container produces a warning', function () {
    $container = new Container();
    $container->add(Foo::class);

    $mockDelegate = new class implements ContainerInterface {
        public function get(string $id): mixed
        {
            return null;
        }
        public function has(string $id): bool
        {
            return false;
        }
    };

    $container->delegate($mockDelegate);

    $result = (new DefinitionAnalyser())->analyse($container);

    $delegateWarnings = array_filter($result->warnings, static fn(string $w): bool => str_contains($w, 'not a ReflectionContainer'));

    expect($delegateWarnings)->not->toBeEmpty();
});

test('reflection container delegate produces no delegate warning', function () {
    $container = new Container();
    $container->add(Foo::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $delegateWarnings = array_filter($result->warnings, static fn(string $w): bool => str_contains($w, 'not a ReflectionContainer'));

    expect($delegateWarnings)->toBeEmpty();
});

test('self-registration class definition produces ClassType', function () {
    $container = new Container();
    $container->add(Foo::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($definition->concreteType)->toBe(ConcreteType::ClassType)
        ->and($definition->concreteClass)->toBe(Foo::class);
});

test('all definitions are added as nodes in the dependency graph', function () {
    $container = new Container();
    $container->add(Foo::class);
    $container->add(Bar::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->dependencyGraph->getDependencies(Foo::class))->toBeArray()
        ->and($result->dependencyGraph->getDependencies(Bar::class))->toBeArray();
});

test('definition with no arguments produces empty resolved arguments', function () {
    $container = new Container();
    $container->add(Foo::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($definition->resolvedArguments)->toBeEmpty();
});

test('definition with no method calls produces empty method calls', function () {
    $container = new Container();
    $container->add(Foo::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($definition->methodCalls)->toBeEmpty();
});

test('multiple tags on a service appear in tag map under their respective keys', function () {
    $container = new Container();
    $container->add(Bar::class)->addTag('repository')->addTag('cacheable');

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->tagMap)->toHaveKey('repository')
        ->and($result->tagMap)->toHaveKey('cacheable')
        ->and($result->tagMap['repository'])->toContain(Bar::class)
        ->and($result->tagMap['cacheable'])->toContain(Bar::class);
});

test('multiple services sharing a tag all appear in the tag map entry', function () {
    $container = new Container();
    $container->add(Foo::class)->addTag('handler');
    $container->add(Bar::class)->addTag('handler');

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->tagMap['handler'])->toContain(Foo::class)
        ->and($result->tagMap['handler'])->toContain(Bar::class);
});

test('analyse returns no errors for a clean container', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class)->addArgument(new ResolvableArgument(Bar::class));

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->hasErrors())->toBeFalse();
});

test('argument compilation error from object argument is collected not thrown', function () {
    $container = new Container();
    $container->add(Foo::class)->addArgument(new ObjectArgument(new Bar()));

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors[0]['errorType'])->toBe('object_argument');
});

test('non-class string concrete produces Literal type', function () {
    $container = new Container();
    $container->add('api.endpoint', new StringArgument('https://example.com/api'));

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, 'api.endpoint');

    expect($definition->concreteType)->toBe(ConcreteType::Literal);
});

test('concrete that is both a known service and existing class is classified as Alias', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(Foo::class, Bar::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($definition->concreteType)->toBe(ConcreteType::Alias);
});

test('autowiring with optional dependency uses default null when dependency is not in container', function () {
    $container = new Container();
    $container->add(Foo::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Foo::class);

    expect($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe('null');
});

test('autowiring with required dependency already registered produces get expression', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(FooWithRequiredDependency::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, FooWithRequiredDependency::class);

    expect($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\Bar')");
});

test('autowiring with required dependency already registered adds dependency graph edge', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(FooWithRequiredDependency::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->dependencyGraph->getDependencies(FooWithRequiredDependency::class))->toContain(Bar::class);
});

test('transitive autowiring synthesises an implicit compiled definition for an unregistered concrete dependency', function () {
    $container = new Container();
    $container->add(FooWithRequiredDependency::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $synthesised = findCompiledDefinition($result->compiledDefinitions, Bar::class);

    expect($synthesised->concreteType)->toBe(ConcreteType::ClassType)
        ->and($synthesised->concreteClass)->toBe(Bar::class)
        ->and($synthesised->shared)->toBeFalse()
        ->and($synthesised->resolvedArguments)->toBeEmpty();
});

test('transitive autowiring resolves primary class argument to synthesised dependency', function () {
    $container = new Container();
    $container->add(FooWithRequiredDependency::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, FooWithRequiredDependency::class);

    expect($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\Bar')");
});

test('inject attribute produces get expression for the injected id', function () {
    $container = new Container();
    $container->add(FooWithAttr::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, FooWithAttr::class);

    expect($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\Bar')");
});

test('inject attribute for unregistered class synthesises a compiled definition', function () {
    $container = new Container();
    $container->add(FooWithAttr::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $synthesised = findCompiledDefinition($result->compiledDefinitions, Bar::class);

    expect($synthesised->concreteType)->toBe(ConcreteType::ClassType);
});

test('unbound interface parameter without default produces error', function () {
    $container = new Container();
    $container->add(FooWithRequiredInterfaceDependency::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $interfaceErrors = array_filter(
        $result->errors,
        static fn(array $e): bool => $e['errorType'] === 'unresolvable_interface_parameter',
    );

    expect($interfaceErrors)->not->toBeEmpty();
});

test('no reflection container delegate skips autowiring and produces empty arguments', function () {
    $container = new Container();
    $container->add(FooWithRequiredDependency::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, FooWithRequiredDependency::class);

    expect($definition->resolvedArguments)->toBeEmpty();
});

test('circular dependency between two autowired classes produces error', function () {
    $container = new Container();
    $container->add(CycleA::class);
    $container->add(CycleB::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $cycleErrors = array_filter(
        $result->errors,
        static fn(array $e): bool => $e['errorType'] === 'circular_dependency',
    );

    expect($cycleErrors)->not->toBeEmpty();
});

test('circular dependency error message contains the cycle path', function () {
    $container = new Container();
    $container->add(CycleA::class);
    $container->add(CycleB::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $cycleErrors = array_values(array_filter(
        $result->errors,
        static fn(array $e): bool => $e['errorType'] === 'circular_dependency',
    ));

    expect($cycleErrors[0]['message'])->toContain('->');
});

test('class with no constructor produces empty resolved arguments when autowired', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, Bar::class);

    expect($definition->resolvedArguments)->toBeEmpty();
});

test('non-public constructor produces error when autowired', function () {
    $container = new Container();
    $container->add(FooWithPrivateConstructor::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $constructorErrors = array_filter(
        $result->errors,
        static fn(array $e): bool => $e['errorType'] === 'non_public_constructor',
    );

    expect($constructorErrors)->not->toBeEmpty();
});

test('parameter with builtin type and default value uses the default literal', function () {
    $container = new Container();
    $container->add(FooWithDefaultScalar::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, FooWithDefaultScalar::class);

    expect($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe("'default'");
});

test('resolve attribute produces simplified get expression and a warning', function () {
    $container = new Container();
    $container->add(Bar::class);
    $container->add(FooWithResolveAttr::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, FooWithResolveAttr::class);

    $resolveWarnings = array_filter(
        $result->warnings,
        static fn(string $w): bool => str_contains($w, '#[Resolve]'),
    );

    expect($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\Bar')")
        ->and($resolveWarnings)->not->toBeEmpty();
});

test('union type parameter without default produces union_type_parameter error', function () {
    $container = new Container();
    $container->add(FooWithUnionType::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $unionErrors = array_filter(
        $result->errors,
        static fn(array $e): bool => $e['errorType'] === 'union_type_parameter',
    );

    expect($unionErrors)->not->toBeEmpty();
});

test('union type parameter with default value uses the default', function () {
    $container = new Container();
    $container->add(FooWithUnionTypeDefault::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, FooWithUnionTypeDefault::class);

    expect($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe('NULL');
});

test('intersection type parameter without default produces intersection_type_parameter error', function () {
    $container = new Container();
    $container->add(FooWithIntersectionType::class);
    $container->delegate(new ReflectionContainer());

    $result = (new DefinitionAnalyser())->analyse($container);

    $intersectionErrors = array_filter(
        $result->errors,
        static fn(array $e): bool => $e['errorType'] === 'intersection_type_parameter',
    );

    expect($intersectionErrors)->not->toBeEmpty();
});

test('contextual argument with ReflectionContainer resolves to concrete get expression', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());
    $container->add(FileCache::class);
    $container->add(LogService::class)
        ->addContextualArgument(CacheInterface::class, FileCache::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, LogService::class);

    expect($result->hasErrors())->toBeFalse()
        ->and($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\FileCache')");
});

test('contextual argument without ReflectionContainer resolves to concrete get expression', function () {
    $container = new Container();
    $container->add(FileCache::class);
    $container->add(LogService::class)
        ->addContextualArgument(CacheInterface::class, FileCache::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $definition = findCompiledDefinition($result->compiledDefinitions, LogService::class);

    expect($result->hasErrors())->toBeFalse()
        ->and($definition->resolvedArguments)->toHaveCount(1)
        ->and($definition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\FileCache')");
});

test('different contextual arguments produce distinct get expressions for each service', function () {
    $container = new Container();
    $container->add(FileCache::class);
    $container->add(RedisCache::class);
    $container->add(LogService::class)
        ->addContextualArgument(CacheInterface::class, FileCache::class);
    $container->add(ApiService::class)
        ->addContextualArgument(CacheInterface::class, RedisCache::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $logDefinition = findCompiledDefinition($result->compiledDefinitions, LogService::class);
    $apiDefinition = findCompiledDefinition($result->compiledDefinitions, ApiService::class);

    expect($result->hasErrors())->toBeFalse()
        ->and($logDefinition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\FileCache')")
        ->and($apiDefinition->resolvedArguments[0])->toBe("\$this->get('League\\\\Container\\\\Test\\\\Asset\\\\RedisCache')");
});

test('contextual argument adds dependency graph edge to concrete class not interface', function () {
    $container = new Container();
    $container->add(FileCache::class);
    $container->add(LogService::class)
        ->addContextualArgument(CacheInterface::class, FileCache::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    expect($result->dependencyGraph->getDependencies(LogService::class))->toContain(FileCache::class)
        ->and($result->dependencyGraph->getDependencies(LogService::class))->not->toContain(CacheInterface::class);
});

test('contextual argument for unregistered concrete synthesises a compiled definition', function () {
    $container = new Container();
    $container->add(LogService::class)
        ->addContextualArgument(CacheInterface::class, FileCache::class);

    $result = (new DefinitionAnalyser())->analyse($container);

    $synthesised = findCompiledDefinition($result->compiledDefinitions, FileCache::class);

    expect($result->hasErrors())->toBeFalse()
        ->and($synthesised->concreteType)->toBe(ConcreteType::ClassType)
        ->and($synthesised->concreteClass)->toBe(FileCache::class);
});

/**
 * @param list<CompiledDefinition> $definitions
 */
function findCompiledDefinition(array $definitions, string $id): CompiledDefinition
{
    foreach ($definitions as $definition) {
        if ($definition->id === $id) {
            return $definition;
        }
    }

    throw new RuntimeException(sprintf('No compiled definition found for "%s"', $id));
}
