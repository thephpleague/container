<?php

namespace League\Container\Definition;

use League\Container\ArgumentResolverTrait;
use League\Container\ContainerInterface;

abstract class AbstractDefinition
{
    use ArgumentResolverTrait;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * Constructor
     *
     * @param string                      $alias
     * @param \League\Container\ContainerInterface $container
     */
    public function __construct($alias, ContainerInterface $container = null)
    {
        $this->alias     = $alias;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function withArgument($arg)
    {
        $this->arguments[] = $arg;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withArguments(array $args)
    {
        foreach ($args as $arg) {
            $this->withArgument($arg);
        }

        return $this;
    }
}
