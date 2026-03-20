<?php

declare(strict_types=1);

namespace League\Container\Compiler;

final class CompilationResult
{
    public function __construct(
        public readonly string $phpSource,
        public readonly string $fullyQualifiedClassName,
        public readonly string $sourceHash,
        public readonly int $serviceCount,
    ) {
    }

    public function writeTo(string $path): void
    {
        $directory = dirname($path);
        $temporaryPath = $directory . DIRECTORY_SEPARATOR . uniqid('compiled_container_', more_entropy: true) . '.php.tmp';

        $bytesWritten = @file_put_contents($temporaryPath, $this->phpSource);

        if ($bytesWritten === false) {
            throw new CompilationException([], sprintf(
                'Failed to write compiled container to temporary file "%s".',
                $temporaryPath,
            ));
        }

        if (@rename($temporaryPath, $path) === false) {
            @unlink($temporaryPath);
            throw new CompilationException([], sprintf(
                'Failed to move compiled container from "%s" to "%s".',
                $temporaryPath,
                $path,
            ));
        }
    }
}
