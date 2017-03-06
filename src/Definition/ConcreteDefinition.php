<?php

namespace League\Container\Definition;

class ConcreteDefinition implements DefinitionInterface
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @var mixed
     */
    private $concrete;

    /**
     * Constructor.
     *
     * @param string $alias
     * @param mixed  $concrete
     */
    public function __construct($alias, & $concrete)
    {
        $this->alias     = $alias;
        $this->concrete  = & $concrete;
    }

    /**
     * Handle instantiation and manipulation of value and return.
     *
     * @param  array $args
     * @return mixed
     */
    public function & build(array $args = [])
    {
        return $this->concrete;
    }

    /**
     * Add an argument to be injected.
     *
     * @param  mixed $arg
     * @return $this
     */
    public function withArgument($arg)
    {
        return $this;
    }

    /**
     * Add multiple arguments to be injected.
     *
     * @param  array $args
     * @return $this
     */
    public function withArguments(array $args)
    {
        return $this;
    }
}