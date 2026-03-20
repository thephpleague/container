<?php

declare(strict_types=1);

use League\Container\Argument\ArgumentReflectorInterface;
use League\Container\Argument\ArgumentReflectorTrait;
use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\Container;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\Baz;

test('resolver resolves arguments via reflection', function () {
    $method = Mockery::mock(ReflectionFunctionAbstract::class);
    $param1 = Mockery::mock(ReflectionParameter::class);
    $param2 = Mockery::mock(ReflectionParameter::class);
    $param3 = Mockery::mock(ReflectionParameter::class);
    $class = Mockery::mock(ReflectionNamedType::class);
    $container = Mockery::mock(Container::class);

    $class->allows('getName')->andReturn('Class');

    $param1->allows('getName')->andReturn('param1');
    $param1->allows('getAttributes')->andReturn([]);
    $param1->shouldReceive('getType')->once()->andReturn($class);
    $param1->allows('isDefaultValueAvailable')->andReturn(false);

    $param2->allows('getName')->andReturn('param2');
    $param2->allows('getAttributes')->andReturn([]);
    $param2->shouldReceive('getType')->once()->andReturn(null);
    $param2->shouldReceive('isDefaultValueAvailable')->once()->andReturn(true);
    $param2->shouldReceive('getDefaultValue')->once()->andReturn('value2');

    $param3->allows('getName')->andReturn('param3');

    $method->shouldReceive('getParameters')->once()->andReturn([$param1, $param2, $param3]);

    $container->shouldReceive('has')->once()->with('Class')->andReturn(true);
    $container->shouldReceive('get')->once()->with('Class')->andReturn('classObject');

    $resolver = new class implements ArgumentReflectorInterface, ArgumentResolverInterface {
        use ArgumentReflectorTrait;
        use ArgumentResolverTrait;
        use ContainerAwareTrait;

        public function getMode(): int
        {
            return ReflectionContainer::ATTRIBUTE_RESOLUTION | ReflectionContainer::AUTO_WIRING;
        }
    };

    $resolver->setContainer($container);

    $args = $resolver->reflectArguments($method, ['param3' => 'value3']);

    expect($args[0])->toBe('classObject');
    expect($args[1])->toBe('value2');
    expect($args[2])->toBe('value3');
});

test('resolves default value argument', function () {
    $resolver = new class implements ArgumentReflectorInterface, ArgumentResolverInterface {
        use ArgumentReflectorTrait;
        use ArgumentResolverTrait;
        use ContainerAwareTrait;

        public function getMode(): int
        {
            return ReflectionContainer::ATTRIBUTE_RESOLUTION | ReflectionContainer::AUTO_WIRING;
        }
    };

    $result = $resolver->reflectArguments((new ReflectionClass(Baz::class))->getConstructor());

    expect($result)->toBe([null]);
});

test('resolver throws exception when reflection does not resolve', function () {
    $method = Mockery::mock(ReflectionFunctionAbstract::class);
    $param = Mockery::mock(ReflectionParameter::class);

    $param->shouldReceive('getName')->andReturn('param1');
    $param->allows('getAttributes')->andReturn([]);
    $param->shouldReceive('getType')->once()->andReturn(null);
    $param->shouldReceive('isDefaultValueAvailable')->once()->andReturn(false);
    $param->allows('getDeclaringClass')->andReturnNull();

    $method->shouldReceive('getParameters')->once()->andReturn([$param]);
    $method->allows('getName')->andReturn('testMethod');

    $resolver = new class implements ArgumentReflectorInterface, ArgumentResolverInterface {
        use ArgumentReflectorTrait;
        use ArgumentResolverTrait;
        use ContainerAwareTrait;

        public function getMode(): int
        {
            return ReflectionContainer::ATTRIBUTE_RESOLUTION | ReflectionContainer::AUTO_WIRING;
        }
    };

    expect(fn() => $resolver->reflectArguments($method))->toThrow(NotFoundException::class);
});
