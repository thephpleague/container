<?php

declare(strict_types=1);

namespace League\Container\Compiler;

final class CompiledDefinition
{
    /**
     * @param list<string> $resolvedArguments
     * @param list<array{method: string, arguments: list<string>}> $methodCalls
     * @param list<string> $tags
     */
    public function __construct(
        public readonly string $id,
        public readonly ConcreteType $concreteType,
        public readonly bool $shared,
        public readonly array $resolvedArguments,
        public readonly array $methodCalls,
        public readonly array $tags,
        public readonly ?string $concreteClass,
        public readonly ?string $factoryClass,
        public readonly ?string $factoryMethod,
    ) {
    }
}
