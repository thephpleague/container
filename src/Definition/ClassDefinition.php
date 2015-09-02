<?php

namespace League\Container\Definition;

use ReflectionClass;

class ClassDefinition extends AbstractDefinition implements ClassDefinitionInterface
{
    /**
     * @var array
     */
    protected $methods = [];

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
    public function build(array $args = [])
    {
        $args       = (empty($args)) ? $this->arguments : $args;
        $resolved   = $this->resolveArguments($args);
        $reflection = new ReflectionClass($this->concrete);
        $instance   = $reflection->newInstanceArgs($resolved);

        return $this->invokeMethods($instance);
    }

    /**
     * Invoke methods on resolved instance.
     *
     * @param  object $instance
     * @return object
     */
    protected function invokeMethods($instance)
    {
        foreach ($this->methods as $method) {
            $args = $this->resolveArguments($method['arguments']);
            call_user_func_array([$instance, $method['method']], $args);
        }

        return $instance;
    }
}
