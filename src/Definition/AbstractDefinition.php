<?php

namespace League\Container\Definition;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ImmutableContainerInterface;

abstract class AbstractDefinition
{
    /**
     * @var string
     */
    protected $alias;

    /**
     * @var mixed
     */
    protected $concrete;

    /**
     * @var \League\Container\ImmutableContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Constructor.
     *
     * @param string                                        $alias
     * @param mixed                                         $concrete
     * @param \League\Container\ImmutableContainerInterface $container
     */
    public function __construct($alias, $concrete, ImmutableContainerInterface $container)
    {
        $this->alias     = $alias;
        $this->concrete  = $concrete;
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

    /**
     * Resolve an array of arguments to concrete dependencies.
     *
     * @param  array $args
     * @return array
     */
    protected function resolveArguments(array $args)
    {
        foreach ($args as &$arg) {
            $arg = (is_string($arg) && ($this->container->has($arg) || class_exists($arg)))
                 ? $this->container->get($arg)
                 : $arg;
        }

        return $args;
    }
}
