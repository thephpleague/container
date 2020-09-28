<?php

declare(strict_types=1);

namespace League\Container\Test\Argument;

use League\Container\Argument\Literal;
use PHPUnit\Framework\TestCase;

class TypedArgumentTest extends TestCase
{
    public function testLiteralArgumentSetsAndGetsArgument(): void
    {
        $arguments = [
            Literal\StringArgument::class   => 'string',
            Literal\ObjectArgument::class   => new class {
            },
            Literal\CallableArgument::class => function () {
            },
            Literal\BooleanArgument::class  => true,
            Literal\IntegerArgument::class  => 1,
            Literal\FloatArgument::class    => 1.23
        ];

        foreach ($arguments as $type => $expected) {
            $argument = new $type($expected);
            self::assertSame($expected, $argument->getValue());
        }
    }
}
