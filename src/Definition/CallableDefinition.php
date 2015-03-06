<?php
namespace League\Container\Definition;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerInterface;

class CallableDefinition extends AbstractDefinition implements
    CallableDefinitionInterface,
    DefinitionInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

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
     * Handle instantiation and manipulation of value and return
     *
     * @param array $args
     *
     * @return mixed
     */
    public function __invoke(array $args = [])
    {
        if (is_array($this->callable) && is_string($this->callable[0])) {
            $registered = (
                isset($this->container[$this->callable[0]]) ||
                class_exists($this->callable[0])
            );

            $this->callable[0] = ($registered) ? $this->container->get($this->callable[0]) : $this->callable[0];
        }

        $args = (empty($args)) ? $this->arguments : $args;

        $resolved = $this->resolveArguments($args);

        return call_user_func_array($this->callable, $resolved);
    }
}