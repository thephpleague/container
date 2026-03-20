<?php

declare(strict_types=1);

namespace League\Container\Test\Compiler;

use League\Container\Compiler\CompilationConfig;
use PHPUnit\Framework\TestCase;

class CompilationConfigTest extends TestCase
{
    public function testDefaultValuesAreUsedWhenNoArgumentsProvided(): void
    {
        $config = new CompilationConfig();

        $this->assertSame('', $config->namespace);
        $this->assertSame('CompiledContainer', $config->className);
    }

    public function testCustomValuesOverrideDefaults(): void
    {
        $config = new CompilationConfig(
            namespace: 'App\Generated',
            className: 'MyContainer',
        );

        $this->assertSame('App\Generated', $config->namespace);
        $this->assertSame('MyContainer', $config->className);
    }

    public function testPropertiesAreReadonly(): void
    {
        $config = new CompilationConfig();

        $this->expectException(\Error::class);
        $config->className = 'mutated';
    }
}
