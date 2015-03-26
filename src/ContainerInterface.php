<?php

namespace League\Container;

interface ContainerInterface extends ImmutableContainerInterface
{
    /**
     * Add an item to the container.
     *
     * @param  string|\League\Container\ServiceProvider $alias
     * @param  mixed|null                               $concrete
     * @param  boolean                                  $singleton
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function add($alias, $concrete = null, $singleton = false);

    /**
     * Convenience method to add an item to the container as a singleton.
     *
     * @param  string     $alias
     * @param  mixed|null $concrete
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function singleton($alias, $concrete = null);

    /**
     * Add a service provider to the container.
     *
     * @param  string|\League\Container\ServiceProvider $provider
     * @param  boolean                                  $boot
     * @return void
     */
    public function addServiceProvider($provider, $boot = false);

    /**
     * Returns a definition of an item to be extended.
     *
     * @param  string $alias
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function extend($alias);

    /**
     * Allows for manipulation of specific types on resolution.
     *
     * @param  string        $type
     * @param  callable|null $callback
     * @return \League\Container\Inflector|void
     */
    public function inflector($type, callable $callback = null);

    /**
     * Invoke a callable via the container.
     *
     * @param  string|callable $callable
     * @param  array           $args
     * @return mixed
     */
    public function call($callable, array $args = []);
}
