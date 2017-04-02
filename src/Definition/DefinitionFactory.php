<?php

namespace League\Container\Definition;

use League\Container\ImmutableContainerAwareTrait;

class DefinitionFactory implements DefinitionFactoryInterface
{
    use ImmutableContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDefinition($alias, $concrete)
    {
        if (is_callable($concrete)) {
            return (new CallableDefinition($alias, $concrete))->setContainer($this->getContainer());
        }

        if (is_string($concrete) && class_exists($concrete)) {
            return (new ClassDefinition($alias, $concrete))->setContainer($this->getContainer());
        }

        // if the item is not definable we just return the value to be stored
        // in the container as an arbitrary value/instance
        return $concrete;
    }
}
