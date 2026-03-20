<?php

declare(strict_types=1);

use League\Container\Argument\Literal;
use League\Container\Argument\LiteralArgument;

test('literal argument sets and gets argument', function () {
    $arguments = [
        Literal\ArrayArgument::class => [],
        Literal\BooleanArgument::class => true,
        Literal\CallableArgument::class => function () {},
        Literal\FloatArgument::class => 1.23,
        Literal\IntegerArgument::class => 1,
        Literal\ObjectArgument::class => new class {},
        Literal\StringArgument::class => 'string',
    ];

    foreach ($arguments as $type => $expected) {
        $argument = new $type($expected);
        expect($argument->getValue())->toBe($expected);
    }
});

test('literal argument throws with wrong argument type', function () {
    expect(fn() => new LiteralArgument(LiteralArgument::TYPE_BOOL, 'blah'))
        ->toThrow(InvalidArgumentException::class);
});
