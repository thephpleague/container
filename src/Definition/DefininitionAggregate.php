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
            $definition = $this->factory($definition);
        }

        $definition->setAlias($id)->setShared($shared);

        $this->definitions[] = $definition;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function factory($concrete): DefinitionInterface
    {
        // TODO handle the never ending struggle of __invoke
        if (is_callable($concrete)) {
            return (new CallableDefinition($concrete))->setContainer($this->getContainer());
        }

        if (is_string($concrete) && class_exists($concrete)) {
            return (new ClassDefinition($concrete))->setContainer($this->getContainer());
        }

        $concrete = ($concrete instanceof RawArgument) ? $concrete : new RawArgument($concrete);

        return new Definition($concrete);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $id)
    {
        foreach ($this->getIterator() as $definition) {
            if ($id === $definition->getAlias()) {
                return $definition->resolve();
            }
        }

        throw new NotFoundException(sprintf('Alias (%s) is not being handled by the container', $id));
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
