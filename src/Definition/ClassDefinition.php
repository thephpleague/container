<?php

namespace League\Container\Definition;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerInterface;

class ClassDefinition extends AbstractDefinition implements ClassDefinitionInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $class;

    /**
     * Constructor
     *
     * @param string                      $alias
     * @param string                      $concrete
     * @param \League\Container\ContainerInterface $container
     */
    public function __construct($alias, $concrete, ContainerInterface $container = null)
    {
        parent::__construct($alias, $container);

        $this->class = $concrete;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethodCall($method, array $args = [])
    {
        $this->methods[] = [
            'method'    => $method,
            'arguments' => $args
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethodCalls(array $methods = [])
    {
        foreach ($methods as $method => $args) {
            $this->withMethodCall($method, $args);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $args = [])
    {
        $args = (empty($args)) ? $this->arguments : $args;
        $resolved = $this->resolveArguments($args);

        $reflection = new \ReflectionClass($this->class);
        $object = $reflection->newInstanceArgs($resolved);

        return $this->invokeMethods($object);
    }

    /**
     * Invoke methods on resolved object
     *
     * @param  object $object
     * @return object
     */
    protected function invokeMethods($object)
    {
        foreach ($this->methods as $method) {
            $args = $this->resolveArguments($method['arguments']);

            call_user_func_array([$object, $method['method']], $args);
        }

        return $object;
    }
}
