<?php

declare(strict_types=1);

namespace League\Container\Definition;

use Generator;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;

class DefinitionAggregate implements DefinitionAggregateInterface
{
    use ContainerAwareTrait;

    public function __construct(protected array $definitions = [])
    {
        $this->definitions = array_filter($this->definitions, static function ($definition) {
            return ($definition instanceof DefinitionInterface);
        });
    }

    public function add(string $id, mixed $definition, bool $overwrite = false): DefinitionInterface
    {
        if (true === $overwrite) {
            $this->remove($id);
        }

        if (false === ($definition instanceof DefinitionInterface)) {
            $definition = new Definition($id, $definition);
        }

        $this->definitions[] = $definition->setAlias($id);

        return $definition;
    }

    public function addShared(string $id, mixed $definition, bool $overwrite = false): DefinitionInterface
    {
        $definition = $this->add($id, $definition, $overwrite);
        return $definition->setShared(true);
    }

    public function has(string $id): bool
    {
        $id = Definition::normaliseAlias($id);

        foreach ($this as $definition) {
            if ($id === $definition->getAlias()) {
                return true;
            }
        }

        return false;
    }

    public function hasTag(string $tag): bool
    {
        foreach ($this as $definition) {
            if ($definition->hasTag($tag)) {
                return true;
            }
        }

        return false;
    }

    public function getDefinition(string $id): DefinitionInterface
    {
        $id = Definition::normaliseAlias($id);

        foreach ($this as $definition) {
            if ($id === $definition->getAlias()) {
                return $definition->setContainer($this->getContainer());
            }
        }

        throw new NotFoundException(sprintf('Alias (%s) is not being handled as a definition.', $id));
    }

    public function resolve(string $id): mixed
    {
        return $this->getDefinition($id)->resolve();
    }

    public function resolveNew(string $id): mixed
    {
        return $this->getDefinition($id)->resolveNew();
    }

    public function resolveTagged(string $tag): array
    {
        $arrayOf = [];

        foreach ($this as $definition) {
            if ($definition->hasTag($tag)) {
                $arrayOf[] = $definition->setContainer($this->getContainer())->resolve();
            }
        }

        return $arrayOf;
    }

    public function resolveTaggedNew(string $tag): array
    {
        $arrayOf = [];

        foreach ($this as $definition) {
            if ($definition->hasTag($tag)) {
                $arrayOf[] = $definition->setContainer($this->getContainer())->resolveNew();
            }
        }

        return $arrayOf;
    }

    public function remove(string $id): void
    {
        $id = Definition::normaliseAlias($id);

        foreach ($this as $key => $definition) {
            if ($id === $definition->getAlias()) {
                unset($this->definitions[$key]);
            }
        }
    }

    public function getIterator(): Generator
    {
        yield from $this->definitions;
    }
}
