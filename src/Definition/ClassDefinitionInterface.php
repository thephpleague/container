<?php

namespace League\Container\Definition;

interface ClassDefinitionInterface extends DefinitionInterface
{
    /**
     * Add a method to be invoked
     *
     * @param  string $method
     * @param  array  $args
     * @return $this
     */
    public function withMethodCall($method, array $args = []);

    /**
     * Add multiple methods to be invoked
     *
     * @param  array $methods
     * @return $this
     */
    public function withMethodCalls(array $methods = []);
}
