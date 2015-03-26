<?php

namespace League\Container\Definition;

use League\Container\ImmutableContainerInterface;

class DefinitionFactory implements DefinitionFactoryInterface
{
    /**
     * @var \League\Container\ImmutableContainerInterface
     */
    protected $container;

    /**
     * Constructor
     *
     * @param \League\Container\ImmutableContainerInterface $container
     */
    public function __construct(ImmutableContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition($alias, $concrete)
    {
        if (is_callable($concrete)) {
            return new CallableDefinition($alias, $concrete, $this->container);
        }

        if (is_string($concrete) && class_exists($concrete)) {
            return new ClassDefinition($alias, $concrete, $this->container);
        }

        // if the item is not defineable we just return the value to be stored
        // in the container as an arbitrary value/instance
        return $concrete;
    }
}
