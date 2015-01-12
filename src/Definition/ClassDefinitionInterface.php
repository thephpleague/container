<?php

namespace League\Container\Definition;

interface ClassDefinitionInterface extends DefinitionInterface
{
    /**
     * Add a method to be invoked
     *
     * @param  string $method
     * @param  array  $args
     * @return \League\Container\Definition\ClassDefinitionInterface
     */
    public function withMethodCall($method, array $args = []);

    /**
     * Add multiple methods to be invoked
     *
     * @param  array $methods
     * @return \League\Container\Definition\ClassDefinitionInterface
     */
    public function withMethodCalls(array $methods = []);
}
