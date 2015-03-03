<?php

namespace League\Container\Definition;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

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
        if (is_array($this->callable) && is_string($this->callable[0])) {
            $registered = (
                isset($this->container[$this->callable[0]]) ||
                class_exists($this->callable[0])
            );

            $this->callable[0] = ($registered) ? $this->container->get($this->callable[0]) : $this->callable[0];
        }

        $resolved = $this->resolveArguments($this->arguments);

        if ($args) {
            $names = $this->getArgumentNames($this->callable);

            $resolved = array_combine(array_splice($names, 0, count($resolved)), $resolved);

            return $this->container->call($this->callable, array_merge($resolved, $args));
        }

        return call_user_func_array($this->callable, $resolved);
    }

    /**
     * @param callable $callable
     *
     * @return array
     */
    protected function getArgumentNames(callable $callable)
    {
        if (is_array($callable)) {
            $reflector = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflector = new ReflectionFunction($callable);
        }

        return array_map(function (ReflectionParameter $param) {
            return $param->getName();
        }, $reflector->getParameters());
    }
}
