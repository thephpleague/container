<?php

declare(strict_types=1);

namespace League\Container;

use League\Container\Definition\DefinitionInterface;
use League\Container\Inflector\InflectorInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

interface DefinitionContainerInterface extends ContainerInterface
{
    /**
     * Add an item to the container.
     *
     * @param string  $id
     * @param mixed   $concrete
     * @param boolean $shared
     *
     * @return DefinitionInterface
     */
    public function add(string $id, $concrete = null, bool $shared = null): DefinitionInterface;

    /**
     * Proxy to add with shared as true.
     *
     * @param string $id
     * @param mixed  $concrete
     *
     * @return DefinitionInterface
     */
    public function share(string $id, $concrete = null): DefinitionInterface;

    /**
     * Get a definition to extend.
     *
     * @param string $id
     *
     * @return DefinitionInterface
     */
    public function extend(string $id): DefinitionInterface;

    /**
     * Add a service provider.
     *
     * @param ServiceProviderInterface|string $provider
     *
     * @return self
     */
    public function addServiceProvider($provider): self;

    /**
     * Allows for manipulation of specific types on resolution.
     *
     * @param string        $type
     * @param callable|null $callback
     *
     * @return InflectorInterface
     */
    public function inflector(string $type, callable $callback = null): InflectorInterface;
}
