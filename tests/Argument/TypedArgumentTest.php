<?php

declare(strict_types=1);

namespace League\Container\Test\Argument;

use InvalidArgumentException;
use League\Container\Argument\Literal;
use League\Container\Argument\LiteralArgument;
use PHPUnit\Framework\TestCase;

class TypedArgumentTest extends TestCase
{
    public function testLiteralArgumentSetsAndGetsArgument(): void
    {
        $arguments = [
            Literal\ArrayArgument::class    => [],
            Literal\BooleanArgument::class  => true,
            Literal\CallableArgument::class => function () {
            },
            Literal\FloatArgument::class    => 1.23,
            Literal\IntegerArgument::class  => 1,
            Literal\ObjectArgument::class   => new class {
            },
            Literal\StringArgument::class   => 'string',
        ];

        foreach ($arguments as $type => $expected) {
            $argument = new $type($expected);
            self::assertSame($expected, $argument->getValue());
        }
    }

    public function testLiteralArgumentThrowsWithWrongArgumentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LiteralArgument(LiteralArgument::TYPE_BOOL, 'blah');
    }
}
