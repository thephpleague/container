<?php

declare(strict_types=1);

namespace League\Container\Definition;

use Generator;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;
use Override;

class DefinitionAggregate implements DefinitionAggregateInterface
{
    use ContainerAwareTrait;

    /** @var array<int, DefinitionInterface> */
    protected array $definitions;

    /** @param array<int, mixed> $definitions */
    public function __construct(array $definitions = [])
    {
        $this->definitions = array_values(array_filter($definitions, static function (mixed $definition): bool {
            return $definition instanceof DefinitionInterface;
        }));
    }

    #[Override]
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

    #[Override]
    public function addShared(string $id, mixed $definition, bool $overwrite = false): DefinitionInterface
    {
        $definition = $this->add($id, $definition, $overwrite);
        return $definition->setShared(true);
    }

    #[Override]
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

    #[Override]
    public function hasTag(string $tag): bool
    {
        foreach ($this as $definition) {
            if ($definition->hasTag($tag)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function getDefinition(string $id): DefinitionInterface
    {
        $id = Definition::normaliseAlias($id);

        foreach ($this as $definition) {
            if ($id === $definition->getAlias()) {
                $definition->setContainer($this->getContainer());
                return $definition;
            }
        }

        throw new NotFoundException(sprintf('Alias (%s) is not being handled as a definition.', $id));
    }

    #[Override]
    public function resolve(string $id): mixed
    {
        return $this->getDefinition($id)->resolve();
    }

    #[Override]
    public function resolveNew(string $id): mixed
    {
        return $this->getDefinition($id)->resolveNew();
    }

    /** @return array<int, mixed> */
    #[Override]
    public function resolveTagged(string $tag): array
    {
        $arrayOf = [];

        foreach ($this as $definition) {
            if ($definition->hasTag($tag)) {
                $definition->setContainer($this->getContainer());
                $arrayOf[] = $definition->resolve();
            }
        }

        return $arrayOf;
    }

    /** @return array<int, mixed> */
    #[Override]
    public function resolveTaggedNew(string $tag): array
    {
        $arrayOf = [];

        foreach ($this as $definition) {
            if ($definition->hasTag($tag)) {
                $definition->setContainer($this->getContainer());
                $arrayOf[] = $definition->resolveNew();
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

    /** @return list<string> */
    #[Override]
    public function getAliases(): array
    {
        $aliases = [];

        foreach ($this as $definition) {
            $aliases[] = $definition->getAlias();
        }

        return $aliases;
    }

    /** @return Generator<int, DefinitionInterface> */
    #[Override]
    public function getIterator(): Generator
    {
        yield from $this->definitions;
    }
}
