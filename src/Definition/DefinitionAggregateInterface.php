<?php declare(strict_types=1);

namespace League\Container\Definition;

use IteratorAggregate;
use League\Container\ContainerAwareInterface;

interface DefinitionAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    /**
     * Add a definition to the aggregate.
     *
     * @param string  $id
     * @param mixed   $definition
     * @param boolean $shared
     *
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function add(string $id, $definition, bool $shared = false): DefinitionInterface;

    /**
     * Factory method to build a definition based on the incoming concretion.
     *
     * @param mixed $concrete
     *
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function factory($concrete): DefinitionInterface;

    /**
     * Resolve and build a concrete value from an id/alias.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function resolve(string $id);
}
