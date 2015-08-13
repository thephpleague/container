<?php

namespace League\Container;

use League\Container\Definition\DefinitionFactory;
use League\Container\Definition\DefinitionFactoryInterface;
use League\Container\Definition\DefinitionInterface;
use League\Container\Inflector\InflectorAggregate;
use League\Container\Inflector\InflectorAggregateInterface;
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
     * @var \League\Container\Definition\DefinitionFactoryInterface
     */
    protected $definitionFactory;

    /**
     * Constructor.
     *
     * @param array                                                                    $config
     * @param \League\Container\ServiceProvider\ServiceProviderAggregateInterface|null $providers
     * @param \League\Container\Inflector\InflectorAggregateInterface|null             $inflectors
     * @param \League\Container\Definition\DefinitionFactoryInterface|null             $definitionFactory
     */
    public function __construct(
        array                             $config            = [],
        ServiceProviderAggregateInterface $providers         = null,
        InflectorAggregateInterface       $inflectors        = null,
        DefinitionFactoryInterface        $definitionFactory = null
    ) {
        // set required dependencies
        $this->providers         = (is_null($providers))
                                 ? (new ServiceProviderAggregate)->setContainer($this)
                                 : $providers;

        $this->inflectors        = (is_null($providers))
                                 ? (new InflectorAggregate)->setContainer($this)
                                 : $inflectors;

        $this->definitionFactory = (is_null($definitionFactory))
                                 ? (new DefinitionFactory)->setContainer($this)
                                 : $definitionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function get($alias, array $args = [])
    {
        if ($this->hasShared($alias)) {
            return $this->shared[$alias];
        }

        if ($this->providers->provides($alias)) {
            $this->providers->register($alias);
        }

        if (array_key_exists($alias, $this->definitions)) {
            return $this->definitions[$alias]->build($args);
        }

        throw new \InvalidArgumentException(
            sprintf('Alias (%s) is not being managed by the container', $alias)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function has($alias)
    {
        if (array_key_exists($alias, $this->definitions) || $this->hasShared($alias)) {
            return true;
        }

        return $this->providers->provides($alias);
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
        if (is_null($concrete)) {
            $concrete = $alias;
        }

        $definition = $this->definitionFactory->getDefinition($alias, $concrete);

        if ($definition instanceof DefinitionInterface) {
            return $definition;
        }

        // dealing with a value that cannot build a definition
        $this->shared[$alias] = $concrete;
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
        if ($this->providers->provides($alias)) {
            $this->providers->register($alias);
        }

        if (array_key_exists($alias, $this->definitions)) {
            return $this->definitions[$alias];
        }

        throw new \InvalidArgumentException(
            sprintf('Unable to extend alias (%s) as it is not being managed as a definition', $alias)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function inflector($type, callable $callback = null)
    {
        return $this->inflectors->add($type, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function call($callable, array $args = [])
    {

    }
}
