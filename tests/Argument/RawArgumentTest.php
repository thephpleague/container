<?php declare(strict_types=1);

namespace League\Container\Test\Argument;

use League\Container\Argument\Argument;
use PHPUnit\Framework\TestCase;

class RawArgumentTest extends TestCase
{
    /**
     * Asserts that a raw argument object can set and get a value.
     */
    public function testRawArgumentSetsAndGetsArgument()
    {
        $arguments = [
            'string',
            new class {
            },
            function () {
            },
            true,
            1,
            1.23
        ];

        foreach ($arguments as $expected) {
            $argument = new Argument($expected);
            $this->assertSame($expected, $argument->getValue());
        }
    }
}
