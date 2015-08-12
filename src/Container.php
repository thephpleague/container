<?php

namespace League\Container;

use League\Container\Inflector\Inflector;
use League\Container\ServiceProvider\ServiceProviderAggregate;
use League\Container\ServiceProvider\ServiceProviderAggregateInterface;

class Container implements ContainerInterface
{
    /**
     * @var \League\Container\Definition\DefinitionInterface[]
     */
    protected $definitions = [];

    /**
     * @var array
     */
    protected $shared = [];

    /**
     * @var \League\Container\Inflector\InflectorAggregateInterface
     */
    protected $inflectors;

    /**
     * @var \League\Container\ServiceProvider\ServiceProviderAggregateInterface
     */
    protected $providers;

    /**
     * Constructor.
     *
     * @param \League\Container\ServiceProvider\ServiceProviderAggregateInterface|null $providers
     * @param \League\Container\Inflector\InflectorAggregateInterface|null             $inflectors
     */
    public function __construct(
        ServiceProviderAggregateInterface $providers  = null,
        InflectorAggregateInterface       $inflectors = null
    ) {
        $this->providers = (is_null($providers))
                         ? (new ServiceProviderAggregate)->setContainer($this)
                         : $providers;

        $this->inflectors = (is_null($providers))
                          ? (new InflectorAggregate)->setContainer($this)
                          : $inflectors;
    }

    /**
     * {@inheritdoc}
     */
    public function get($alias, array $args = [])
    {

    }

    /**
     * {@inheritdoc}
     */
    public function has($alias)
    {
        if (array_key_exists($alias, $this->definitions) || $this->hasShared($alias)) {
            return true;
        }

        return $this->provider->provides($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function hasShared($alias)
    {
        return (array_key_exists($alias, $this->shared));
    }

    /**
     * {@inheritdoc}
     */
    public function add($alias, $concrete = null, $share = false)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function share($alias, $concrete = null)
    {
        return $this->add($alias, $concrete, true);
    }

    /**
     * {@inheritdoc}
     */
    public function addServiceProvider($provider)
    {
        $this->providers->add($provider);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function extend($alias)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function inflector($type, callable $callback = null)
    {
        if (is_null($callback)) {
            $inflector = new Inflector;
            $this->inflectors[$type] = $inflector;

            return $inflector;
        }

        $this->inflectors[$type] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function call($callable, array $args = [])
    {

    }
}
