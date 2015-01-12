<?php

namespace League\Container\Definition;

use League\Container\ContainerInterface;

class Factory
{
    /**
     * Return a definition based on type of concrete
     *
     * @param  string                       $alias
     * @param  mixed                        $concrete
     * @param  \League\Container\ContainerInterface  $container
     * @param  boolean                      $callable
     * @return mixed
     */
    public function __invoke($alias, $concrete, ContainerInterface $container, $callable = false)
    {
        if ($callable === true) {
            return new CallableDefinition($alias, $concrete, $container);
        }

        if ($concrete instanceof \Closure) {
            return new ClosureDefinition($alias, $concrete, $container);
        }

        if (is_string($concrete) && class_exists($concrete)) {
            return new ClassDefinition($alias, $concrete, $container);
        }

        // if the item is not defineable we just return the value to be stored
        // in the container as an arbitrary value/instance
        return $concrete;
    }
}
