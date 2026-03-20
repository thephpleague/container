<?php

declare(strict_types=1);

namespace League\Container\Definition;

use League\Container\ContainerAwareInterface;

interface DefinitionInterface extends ContainerAwareInterface
{
    public function addArgument(mixed $arg): DefinitionInterface;

    /** @param array<int, mixed> $args */
    public function addArguments(array $args): DefinitionInterface;

    /** @param array<int, mixed> $args */
    public function addMethodCall(string $method, array $args = []): DefinitionInterface;

    /** @param array<string, array<int, mixed>> $methods */
    public function addMethodCalls(array $methods = []): DefinitionInterface;

    public function addTag(string $tag): DefinitionInterface;
    public function getAlias(): string;
    public function getConcrete(): mixed;

    /** @return list<string> */
    public function getTags(): array;

    public function hasTag(string $tag): bool;
    public function isShared(): bool;
    public function resolve(): mixed;
    public function resolveNew(): mixed;
    public function setAlias(string $id): DefinitionInterface;
    public function setConcrete(mixed $concrete): DefinitionInterface;
    public function setShared(bool $shared): DefinitionInterface;
}
