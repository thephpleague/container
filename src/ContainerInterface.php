<?php

namespace League\Container;

interface ContainerInterface extends ImmutableContainerInterface
{
    /**
     * Add an item to the container.
     *
     * @param  string     $alias
     * @param  mixed|null $concrete
     * @param  boolean    $share
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function add($alias, $concrete = null, $share = false);

    /**
     * Convenience method to add an item to the container as a shared item.
     *
     * @param  string     $alias
     * @param  mixed|null $concrete
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function share($alias, $concrete = null);

    /**
     * Add a service provider to the container.
     *
     * @param  string|\League\Container\ServiceProvider\ServiceProviderInterface $provider
     * @return void
     */
    public function addServiceProvider($provider);

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
     * @return \League\Container\Inflector\Inflector|void
     */
    public function inflector($type, callable $callback = null);

    /**
     * Invoke a callable via the container.
     *
     * @param  callable $callable
     * @param  array    $args
     * @return mixed
     */
    public function call(callable $callable, array $args = []);
}
