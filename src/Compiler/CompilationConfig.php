<?php

declare(strict_types=1);

namespace League\Container\Compiler;

final class CompilationConfig
{
    public function __construct(
        public readonly string $namespace = '',
        public readonly string $className = 'CompiledContainer',
    ) {
    }
}
