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
     * Checks whether alias exists as definition.
     *
     * @param string $id
     *
     * @return boolean
     */
    public function has(string $id): bool;

    /**
     * Get the definition to be extended.
     *
     * @param string $id
     *
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function getDefinition(string $id): DefinitionInterface;

    /**
     * Resolve and build a concrete value from an id/alias.
     *
     * @param string  $id
     * @param array   $args
     * @param boolean $new
     *
     * @return mixed
     */
    public function resolve(string $id, array $args = [], bool $new = false);
}
