<?php declare(strict_types=1);

namespace League\Container\Definition;

use Generator;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;

class DefinitionAggregate implements DefinitionAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var DefinitionInterface[]
     */
    protected $definitions = [];

    /**
     * Construct.
     *
     * @param DefinitionInterface[] $definitions
     */
    public function __construct(array $definitions = [])
    {
        $definitions = array_filter($definitions, function ($definition) {
            return ($definition instanceof DefinitionInterface);
        });

        foreach ($definitions as $definition) {
          $this->definitions[$definition->getAlias()] = $definition;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $id, $definition, bool $shared = false) : DefinitionInterface
    {
        if (!$definition instanceof DefinitionInterface) {
            $definition = new Definition($id, $definition);
        }

        $this->definitions[$id] = $definition
            ->setAlias($id)
            ->setShared($shared)
        ;

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id) : bool
    {
      return array_key_exists($id, $this->definitions);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTag(string $tag) : bool
    {
        foreach ($this->getIterator() as $definition) {
            if ($definition->hasTag($tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $id) : DefinitionInterface
    {
        if (array_key_exists($id, $this->definitions)) {
            return $this->definitions[$id]->setContainer($this->getContainer());
        }

        throw new NotFoundException(sprintf('Alias (%s) is not being handled as a definition.', $id));
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $id, bool $new = false)
    {
        return $this->getDefinition($id)->resolve($new);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveTagged(string $tag, bool $new = false) : array
    {
        $arrayOf = [];

        foreach ($this->getIterator() as $definition) {
            if ($definition->hasTag($tag)) {
                $arrayOf[] = $definition->setLeagueContainer($this->getLeagueContainer())->resolve($new);
            }
        }

        return $arrayOf;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator() : Generator
    {
        foreach ($this->definitions as $definition) {
            yield $definition;
        }
    }
}
