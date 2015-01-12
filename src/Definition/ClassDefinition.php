<?php

namespace League\Container\Definition;

use League\Container\ContainerInterface;

class ClassDefinition extends AbstractDefinition implements ClassDefinitionInterface
{

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
    public function __construct($alias, $concrete, ContainerInterface $container)
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
            $reflection = new \ReflectionMethod($object, $method['method']);

            $args = [];

            foreach ($method['arguments'] as $arg) {
                $args[] = ($this->container->isRegistered($arg)) ? $this->container->get($arg) : $arg;
            }

            $reflection->invokeArgs($object, $args);
        }

        return $object;
    }
}
