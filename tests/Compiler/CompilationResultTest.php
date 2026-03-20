<?php

declare(strict_types=1);

namespace League\Container\Test\Compiler;

use League\Container\Compiler\CompilationException;
use League\Container\Compiler\CompilationResult;
use PHPUnit\Framework\TestCase;

class CompilationResultTest extends TestCase
{
    public function testConstructorStoresAllProperties(): void
    {
        $result = new CompilationResult(
            phpSource: '<?php class CompiledContainer {}',
            fullyQualifiedClassName: 'App\Generated\CompiledContainer',
            sourceHash: hash('sha256', 'test'),
            serviceCount: 42,
        );

        $this->assertSame('<?php class CompiledContainer {}', $result->phpSource);
        $this->assertSame('App\Generated\CompiledContainer', $result->fullyQualifiedClassName);
        $this->assertSame(hash('sha256', 'test'), $result->sourceHash);
        $this->assertSame(42, $result->serviceCount);
    }

    public function testWriteToCreatesFileAtGivenPath(): void
    {
        $targetPath = sys_get_temp_dir() . '/compilation_result_test_' . uniqid() . '.php';

        $result = new CompilationResult(
            phpSource: '<?php // compiled',
            fullyQualifiedClassName: 'CompiledContainer',
            sourceHash: hash('sha256', 'test'),
            serviceCount: 1,
        );

        $result->writeTo($targetPath);

        $this->assertFileExists($targetPath);
        $this->assertSame('<?php // compiled', file_get_contents($targetPath));

        unlink($targetPath);
    }

    public function testWriteToPerformsAtomicWriteWithNoTemporaryFileLeft(): void
    {
        $targetPath = sys_get_temp_dir() . '/compilation_result_atomic_test_' . uniqid() . '.php';
        $directory = sys_get_temp_dir();

        $filesBefore = glob($directory . '/compiled_container_*.php.tmp');

        $result = new CompilationResult(
            phpSource: '<?php // atomic',
            fullyQualifiedClassName: 'CompiledContainer',
            sourceHash: hash('sha256', 'atomic'),
            serviceCount: 0,
        );

        $result->writeTo($targetPath);

        $filesAfter = glob($directory . '/compiled_container_*.php.tmp');

        $this->assertSame($filesBefore, $filesAfter);

        unlink($targetPath);
    }

    public function testPropertiesAreReadonly(): void
    {
        $result = new CompilationResult(
            phpSource: '<?php',
            fullyQualifiedClassName: 'C',
            sourceHash: 'abc',
            serviceCount: 0,
        );

        $this->expectException(\Error::class);
        $result->phpSource = 'mutated';
    }
}
