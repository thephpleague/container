<?php

namespace League\Container;

interface ImmutableContainerInterface
{
    /**
     * Retrieve an item from the container.
     *
     * @param  string $alias
     * @param  array  $args
     * @return mixed
     */
    public function get($alias, array $args = []);

    /**
     * Invoke a callable via the container.
     *
     * @param  string|callable $callable
     * @param  array           $args
     * @return mixed
     */
    public function call($callable, array $args = []);

    /**
     * Returns by default a boolean if the alias is registered with the container.
     *
     * @param  string  $alias
     * @param  boolean $verbose
     * @return boolean|array
     */
    public function has($alias, $verbose = false);
}
