<?php

namespace League\Container\Definition;

use League\Container\ContainerInterface;

interface FactoryInterface
{
    /**
     * Return a definition based on type of concrete
     *
     * @param  string                               $alias
     * @param  mixed                                $concrete
     * @param  \League\Container\ContainerInterface $container
     * @param  boolean                              $callable
     * @return mixed
     */
    public function __invoke($alias, $concrete, ContainerInterface $container, $callable = false);
}
