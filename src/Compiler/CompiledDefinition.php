<?php

declare(strict_types=1);

namespace League\Container\Compiler;

final readonly class CompiledDefinition
{
    /**
     * @param list<string> $resolvedArguments
     * @param list<array{method: string, arguments: list<string>}> $methodCalls
     * @param list<string> $tags
     */
    public function __construct(
        public string $id,
        public ConcreteType $concreteType,
        public bool $shared,
        public array $resolvedArguments,
        public array $methodCalls,
        public array $tags,
        public ?string $concreteClass,
        public ?string $factoryClass,
        public ?string $factoryMethod,
    ) {}
}
