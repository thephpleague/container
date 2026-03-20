<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\Exception\ContainerException;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\Test\Asset\ApiService;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\CacheInterface;
use League\Container\Test\Asset\CycleA;
use League\Container\Test\Asset\CycleB;
use League\Container\Test\Asset\FileCache;
use League\Container\Test\Asset\Foo;
use League\Container\Test\Asset\LogService;
use League\Container\Test\Asset\RedisCache;
use League\Container\Test\Asset\SharedService;

test('container adds and gets', function () {
    $container = new Container();
    $container->add(Foo::class);

    expect($container->has(Foo::class))->toBeTrue();
    expect($container->get(Foo::class))->toBeInstanceOf(Foo::class);
});

test('container adds and gets recursively', function () {
    $container = new Container();
    $container->add(Bar::class, Foo::class);
    $container->add(Foo::class);

    expect($container->has(Foo::class))->toBeTrue();
    expect($container->get(Bar::class))->toBeInstanceOf(Foo::class);
});

test('container adds and gets shared', function () {
    $container = new Container();
    $container->addShared(Foo::class);

    expect($container->has(Foo::class))->toBeTrue();

    $fooOne = $container->get(Foo::class);
    $fooTwo = $container->get(Foo::class);

    expect($fooOne)->toBeInstanceOf(Foo::class);
    expect($fooTwo)->toBeInstanceOf(Foo::class);
    expect($fooTwo)->toBe($fooOne);
});

test('container adds and gets shared by default', function () {
    $container = new Container();
    $container->defaultToShared();
    $container->add(Foo::class);

    expect($container->has(Foo::class))->toBeTrue();

    $fooOne = $container->get(Foo::class);
    $fooTwo = $container->get(Foo::class);

    expect($fooOne)->toBeInstanceOf(Foo::class);
    expect($fooTwo)->toBeInstanceOf(Foo::class);
    expect($fooTwo)->toBe($fooOne);
});

test('container adds and gets from tag', function () {
    $container = new Container();
    $container->add(Foo::class)->addTag('foobar');
    $container->add(Bar::class)->addTag('foobar');

    expect($container->has(Foo::class))->toBeTrue();

    $arrayOf = $container->get('foobar');

    expect($container->has('foobar'))->toBeTrue();
    expect($arrayOf)->toBeArray();
    expect($arrayOf)->toHaveCount(2);
    expect($arrayOf[0])->toBeInstanceOf(Foo::class);
    expect($arrayOf[1])->toBeInstanceOf(Bar::class);
});

test('container adds and gets new from tag', function () {
    $container = new Container();
    $container->add(Foo::class)->addTag('foobar');
    $container->add(Bar::class)->addTag('foobar');

    expect($container->has(Foo::class))->toBeTrue();

    $arrayOf = $container->get('foobar');

    expect($container->has('foobar'))->toBeTrue();
    expect($arrayOf)->toBeArray();
    expect($arrayOf)->toHaveCount(2);
    expect($arrayOf[0])->toBeInstanceOf(Foo::class);
    expect($arrayOf[1])->toBeInstanceOf(Bar::class);

    $arrayOfTwo = $container->getNew('foobar');
    expect($arrayOfTwo)->not->toBe($arrayOf);
});

test('container adds and gets with service provider', function () {
    $provider = new class extends AbstractServiceProvider {
        public function provides(string $id): bool
        {
            return $id === Foo::class;
        }

        public function register(): void
        {
            $this->getContainer()->add(Foo::class);
        }
    };

    $container = new Container();
    $container->addServiceProvider($provider);

    expect($container->has(Foo::class))->toBeTrue();
    expect($container->get(Foo::class))->toBeInstanceOf(Foo::class);
});

test('throws when service provider lies', function () {
    $liar = new class extends AbstractServiceProvider {
        public function provides(string $id): bool
        {
            return true;
        }

        public function register(): void {}
    };

    $container = new Container();
    $container->addServiceProvider($liar);

    expect($container->has('lie'))->toBeTrue();

    expect(fn() => $container->get('lie'))->toThrow(ContainerException::class);
});

test('container adds and gets from delegate', function () {
    $delegate = new ReflectionContainer();
    $container = new Container();
    $container->delegate($delegate);

    expect($container->get(Foo::class))->toBeInstanceOf(Foo::class);
});

test('container throws when cannot get service', function () {
    $container = new Container();

    expect($container->has(Foo::class))->toBeFalse();
    expect(fn() => $container->get(Foo::class))->toThrow(NotFoundException::class);
});

test('container can extend definition', function () {
    $container = new Container();
    $container->add(Foo::class);
    $definition = $container->extend(Foo::class);

    expect($definition->getAlias())->toBe(Foo::class);
    expect($definition->getConcrete())->toBe(Foo::class);
});

test('container can extend definition from service provider', function () {
    $provider = new class extends AbstractServiceProvider {
        public function provides(string $id): bool
        {
            return $id === Foo::class;
        }

        public function register(): void
        {
            $this->getContainer()->add(Foo::class);
        }
    };

    $container = new Container();
    $container->addServiceProvider($provider);
    $definition = $container->extend(Foo::class);

    expect($definition->getAlias())->toBe(Foo::class);
    expect($definition->getConcrete())->toBe(Foo::class);
});

test('container throws when cannot get definition to extend', function () {
    $container = new Container();

    expect($container->has(Foo::class))->toBeFalse();
    expect(fn() => $container->extend(Foo::class))->toThrow(NotFoundException::class);
});

test('non existent class resolves as string', function () {
    $container = new Container();
    $container->add('NonExistent');

    expect($container->has('NonExistent'))->toBeTrue();
    expect($container->get('NonExistent'))->toBe('NonExistent');
});

test('runtime overwrite', function () {
    $concreteOne = new stdClass();
    $concreteTwo = new stdClass();

    $container = new Container();
    $container->add('foo', $concreteOne);

    expect($container->get('foo'))->toBe($concreteOne);

    $container->add('foo', $concreteTwo, true);

    expect($container->get('foo'))->toBe($concreteTwo);
    expect($container->get('foo'))->not->toBe($concreteOne);
});

test('default overwrite', function () {
    $concreteOne = new stdClass();
    $concreteTwo = new stdClass();

    $container = new Container();
    $container->defaultToOverwrite();
    $container->add('foo', $concreteOne);

    expect($container->get('foo'))->toBe($concreteOne);

    $container->add('foo', $concreteTwo);

    expect($container->get('foo'))->toBe($concreteTwo);
    expect($container->get('foo'))->not->toBe($concreteOne);
});

test('get delegate returns matching delegate', function () {
    $container = new Container();
    $delegate = new ReflectionContainer();
    $container->delegate($delegate);

    expect($container->getDelegate(ReflectionContainer::class))->toBe($delegate);
});

test('get delegate throws when no delegate of type exists', function () {
    $container = new Container();

    expect(fn() => $container->getDelegate(ReflectionContainer::class))
        ->toThrow(NotFoundException::class, 'No delegate container of type');
});

test('not found exception suggests similar service name', function () {
    $container = new Container();
    $container->add('App\\Service\\Mailer');

    expect(fn() => $container->get('App\\Service\\Mailor'))
        ->toThrow(NotFoundException::class, 'Did you mean');
});

test('not found exception does not suggest when no close match exists', function () {
    $container = new Container();
    $container->add('App\\Service\\Mailer');

    expect(fn() => $container->get('CompletelyDifferent'))
        ->toThrow(NotFoundException::class);

    try {
        $container->get('CompletelyDifferent');
    } catch (NotFoundException $e) {
        expect($e->getMessage())->not->toContain('Did you mean');
    }
});

test('service provider lied error includes provider class name', function () {
    $liar = new class extends AbstractServiceProvider {
        public function provides(string $id): bool
        {
            return true;
        }

        public function register(): void {}
    };

    $container = new Container();
    $container->addServiceProvider($liar);

    try {
        $container->get('lie');
    } catch (ContainerException $e) {
        expect($e->getMessage())->toContain('claimed to provide');
        expect($e->getMessage())->toContain('AbstractServiceProvider');

        return;
    }

    test()->fail('Expected ContainerException was not thrown');
});

test('circular dependency throws with descriptive error', function () {
    $container = new Container();
    $container->add(CycleA::class)->addArgument(CycleB::class);
    $container->add(CycleB::class)->addArgument(CycleA::class);

    expect(fn() => $container->get(CycleA::class))
        ->toThrow(ContainerException::class, 'Circular dependency detected');
});

test('circular dependency error message includes full resolution chain', function () {
    $container = new Container();
    $container->add(CycleA::class)->addArgument(CycleB::class);
    $container->add(CycleB::class)->addArgument(CycleA::class);

    try {
        $container->get(CycleA::class);
    } catch (ContainerException $e) {
        expect($e->getMessage())->toContain(CycleA::class);
        expect($e->getMessage())->toContain(CycleB::class);
        expect($e->getMessage())->toContain('->');

        return;
    }

    test()->fail('Expected ContainerException was not thrown');
});

test('resolution stack is cleaned up after failed resolution', function () {
    $container = new Container();

    try {
        $container->get('nonexistent');
    } catch (NotFoundException) {
    }

    $container->add(Foo::class);
    expect($container->get(Foo::class))->toBeInstanceOf(Foo::class);
});

test('not found exception includes resolution chain for nested dependency failures', function () {
    $container = new Container();
    $container->add(Foo::class);

    expect(fn() => $container->get(Bar::class))
        ->toThrow(NotFoundException::class);
});

test('getDefinitionIds returns all registered service IDs', function () {
    $container = new Container();
    $container->add(Foo::class);
    $container->add(Bar::class);

    $ids = $container->getDefinitionIds();

    expect($ids)->toContain(Foo::class);
    expect($ids)->toContain(Bar::class);
    expect($ids)->toHaveCount(2);
});

test('getDefinitionIds returns empty array when no services are registered', function () {
    $container = new Container();

    expect($container->getDefinitionIds())->toBe([]);
});

test('getServiceProviderIds returns IDs claimed by providers', function () {
    $provider = new class extends AbstractServiceProvider {
        #[Override]
        public function provides(string $id): bool
        {
            return in_array($id, [Foo::class, Bar::class], true);
        }

        #[Override]
        public function getProvidedIds(): array
        {
            return [Foo::class, Bar::class];
        }

        #[Override]
        public function register(): void {}
    };

    $container = new Container();
    $container->addServiceProvider($provider);

    $ids = $container->getServiceProviderIds();

    expect($ids)->toContain(Foo::class);
    expect($ids)->toContain(Bar::class);
    expect($ids)->toHaveCount(2);
});

test('getServiceProviderIds returns empty array when no providers are registered', function () {
    $container = new Container();

    expect($container->getServiceProviderIds())->toBe([]);
});

test('contextual binding resolves different implementations per consumer', function () {
    $container = new Container();
    $container->add(FileCache::class);
    $container->add(RedisCache::class);
    $container->add(LogService::class)
        ->addContextualArgument(CacheInterface::class, FileCache::class);
    $container->add(ApiService::class)
        ->addContextualArgument(CacheInterface::class, RedisCache::class);

    $log = $container->get(LogService::class);
    $api = $container->get(ApiService::class);

    expect($log)->toBeInstanceOf(LogService::class);
    expect($log->cache)->toBeInstanceOf(FileCache::class);
    expect($api)->toBeInstanceOf(ApiService::class);
    expect($api->cache)->toBeInstanceOf(RedisCache::class);
});

test('contextual binding with shared definitions returns same instance', function () {
    $container = new Container();
    $container->add(FileCache::class);
    $container->addShared(LogService::class)
        ->addContextualArgument(CacheInterface::class, FileCache::class);

    $log1 = $container->get(LogService::class);
    $log2 = $container->get(LogService::class);

    expect($log1)->toBe($log2);
    expect($log1->cache)->toBeInstanceOf(FileCache::class);
});

test('getContextualArguments returns stored contextual arguments', function () {
    $container = new Container();
    $definition = $container->add(LogService::class)
        ->addContextualArgument(CacheInterface::class, FileCache::class);

    $contextual = $definition->getContextualArguments();

    expect($contextual)->toHaveKey(CacheInterface::class);
    expect($contextual[CacheInterface::class])->toBe(FileCache::class);
});

test('getDefinitionIds does not include provider services before they are resolved', function () {
    $provider = new class extends AbstractServiceProvider {
        #[Override]
        public function provides(string $id): bool
        {
            return $id === Foo::class;
        }

        #[Override]
        public function getProvidedIds(): array
        {
            return [Foo::class];
        }

        #[Override]
        public function register(): void
        {
            $this->getContainer()->add(Foo::class);
        }
    };

    $container = new Container();
    $container->addServiceProvider($provider);

    expect($container->getDefinitionIds())->not->toContain(Foo::class);
    expect($container->getServiceProviderIds())->toContain(Foo::class);
});

test('shared attribute is respected when resolving through delegate', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer());

    $first = $container->get(SharedService::class);
    $second = $container->get(SharedService::class);

    expect($first)->toBe($second);
});
