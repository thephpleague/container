<?php

declare(strict_types=1);

use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\Argument\Literal;
use League\Container\Container;
use League\Container\ContainerAwareTrait;

test('resolver resolves from container', function () {
    $resolver = new class implements ArgumentResolverInterface {
        use ArgumentResolverTrait;
        use ContainerAwareTrait;
    };

    $container = Mockery::mock(Container::class);

    $container->shouldReceive('has')->twice()->andReturn(true, false);
    $container->shouldReceive('get')->once()->with('alias1')->andReturn($resolver);

    $resolver->setContainer($container);

    $args = $resolver->resolveArguments(['alias1', 'alias2']);

    expect($args[0])->toBe($resolver);
    expect($args[1])->toBe('alias2');
});

test('resolver resolves literal arguments', function () {
    $resolver = new class implements ArgumentResolverInterface {
        use ArgumentResolverTrait;
        use ContainerAwareTrait;
    };

    $container = Mockery::mock(Container::class);

    $container->shouldReceive('has')->once()->andReturn(true);
    $container->shouldReceive('get')->once()->with('alias1')->andReturn(new Literal\StringArgument('value1'));

    $resolver->setContainer($container);

    $args = $resolver->resolveArguments(['alias1', new Literal\StringArgument('value2')]);

    expect($args[0])->toBe('value1');
    expect($args[1])->toBe('value2');
});
