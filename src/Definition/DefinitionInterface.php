<?php

namespace League\Container\Definition;

interface DefinitionInterface
{
    /**
     * Handle instantiation and manipulation of value and return.
     *
     * @param  array $args
     * @return mixed
     */
    public function build(array $args = []);

    /**
     * Add an argument to be injected.
     *
     * @param  mixed $arg
     * @return $this
     */
    public function withArgument($arg);

    /**
     * Add multiple arguments to be injected.
     *
     * @param  array $args
     * @return $this
     */
    public function withArguments(array $args);
}
