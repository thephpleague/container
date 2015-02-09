<?php

namespace League\Container\Definition;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerInterface;

class CallableDefinition extends AbstractDefinition implements DefinitionInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var callable
     */
    protected $callable;

    /**
     * Constructor
     *
     * @param string                               $alias
     * @param string|callable                      $concrete
     * @param \League\Container\ContainerInterface $container
     */
    public function __construct($alias, $concrete, ContainerInterface $container = null)
    {
        parent::__construct($alias, $container);

        $this->callable = $concrete;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $args = [])
    {
        $args = (empty($args)) ? $this->arguments : $args;
        $resolved = $this->resolveArguments($args);

        if (is_array($this->callable) && is_string($this->callable[0])) {
            $registered = (
                $this->container->isRegistered($this->callable[0])        ||
                $this->container->isSingleton($this->callable[0])         ||
                $this->container->isInServiceProvider($this->callable[0]) ||
                class_exists($this->callable[0])
            );

            $this->callable[0] = ($registered === true) ? $this->container->get($this->callable[0]) : $this->callable[0];
        }

        return call_user_func_array($this->callable, $resolved);
    }
}
