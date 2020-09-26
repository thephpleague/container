<?php

declare(strict_types=1);

namespace League\Container\Test\Argument;

use League\Container\Argument\{Typed, TypedArgument};
use PHPUnit\Framework\TestCase;

class TypedArgumentTest extends TestCase
{
    /**
     * Asserts that a raw argument object can set and get a value.
     */
    public function testRawArgumentSetsAndGetsArgument(): void
    {
        $arguments = [
            Typed\StringArgument::class   => 'string',
            Typed\ObjectArgument::class   => new class {
            },
            Typed\CallableArgument::class => function () {
            },
            Typed\BooleanArgument::class  => true,
            Typed\IntegerArgument::class  => 1,
            Typed\FloatArgument::class    => 1.23
        ];

        foreach ($arguments as $type => $expected) {
            $argument = new $type($expected);
            self::assertSame($expected, $argument->getValue());
        }
    }
}
