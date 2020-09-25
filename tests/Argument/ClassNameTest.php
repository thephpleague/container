<?php declare(strict_types=1);

namespace League\Container\Test;

use League\Container\Argument\ClassName;
use PHPUnit\Framework\TestCase;

class ClassNameTest extends TestCase
{
    /**
     * Asserts that a raw argument object can set and get a value.
     */
    public function testClassNameSetsAndGetsArgument()
    {
        $arguments = [
            'string',
            'string2'
        ];

        foreach ($arguments as $expected) {
            $argument = new ClassName($expected);
            $this->assertSame($expected, $argument->getClassName());
        }
    }
}
