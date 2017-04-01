<?php declare(strict_types=1);

namespace League\Container\Definition;

use Generator;
use League\Container\Argument\RawArgument;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;

class DefinitionAggregate implements DefinitionAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var \League\Container\Definition\DefinitionInterface[]
     */
    protected $definitions = [];

    /**
     * {@inheritdoc}
     */
    public function add(string $id, $definition, bool $shared = false): DefinitionInterface
    {
        if (! $definition instanceof DefinitionInterface) {
            $definition = (new Definition($id, $definition));
        }

        $this->definitions[] = $definition
            ->setContainer($this->getContainer())
            ->setAlias($id)
            ->setShared($shared)
        ;

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        foreach ($this->getIterator() as $definition) {
            if ($id === $definition->getAlias()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $id): DefinitionInterface
    {
        foreach ($this->getIterator() as $definition) {
            if ($id === $definition->getAlias()) {
                return $definition;
            }
        }

        throw new NotFoundException(sprintf('Alias (%s) is not being handled as a definition.', $id));
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $id, array $args = [], bool $new = false)
    {
        return $this->getDefinition($id)->resolve($args, $new);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Generator
    {
        $count = count($this->definitions);

        for ($i = 0; $i < $count; $i++) {
            yield $this->definitions[$i];
        }
    }
}
