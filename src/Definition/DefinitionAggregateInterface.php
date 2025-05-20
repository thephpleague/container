<?php

declare(strict_types=1);

namespace League\Container\Definition;

use IteratorAggregate;
use League\Container\ContainerAwareInterface;

interface DefinitionAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add(string $id, mixed $definition, bool $overwrite = false): DefinitionInterface;
    public function addShared(string $id, mixed $definition, bool $overwrite = false): DefinitionInterface;
    public function getDefinition(string $id): DefinitionInterface;
    public function has(string $id): bool;
    public function hasTag(string $tag): bool;
    public function resolve(string $id): mixed;
    public function resolveNew(string $id): mixed;
    public function resolveTagged(string $tag): array;
    public function resolveTaggedNew(string $tag): array;
}
