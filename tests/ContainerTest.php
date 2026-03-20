<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\Exception\ContainerException;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;

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
