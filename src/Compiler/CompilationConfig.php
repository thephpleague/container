<?php

declare(strict_types=1);

namespace League\Container\Compiler;

final readonly class CompilationConfig
{
    public function __construct(
        public string $namespace = '',
        public string $className = 'CompiledContainer',
    ) {}
}
