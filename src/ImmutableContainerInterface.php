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
     * Returns a boolean to determine if an alias is registered with the container.
     *
     * @param  string  $alias
     * @return boolean|array
     */
    public function has($alias);
}
