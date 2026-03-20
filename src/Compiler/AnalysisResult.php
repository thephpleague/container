<?php

declare(strict_types=1);

namespace League\Container\Compiler;

final readonly class AnalysisResult
{
    /**
     * @param list<CompiledDefinition> $compiledDefinitions
     * @param array<string, list<string>> $tagMap
     * @param list<array{serviceId: string, errorType: string, message: string, suggestedFix: string}> $errors
     * @param list<string> $warnings
     */
    public function __construct(
        public array $compiledDefinitions,
        public DependencyGraph $dependencyGraph,
        public array $tagMap,
        public array $errors,
        public array $warnings,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}
