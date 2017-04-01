<?php declare(strict_types=1);

namespace League\Container\Definition;

use League\Container\ContainerAwareInterface;

interface DefinitionInterface extends ContainerAwareInterface
{
    /**
     * Set the alias of the definition.
     *
     * @param string $id
     */
    public function setAlias(string $id): self;

    /**
     * Get the alias of the definition.
     *
     * @return string
     */
    public function getAlias(): string;

    /**
     * Set whether this is a shared definition.
     *
     * @param boolean $shared
     *
     * @return self
     */
    public function setShared(bool $shared): self;

    /**
     * Is this a shared definition?
     *
     * @return boolean
     */
    public function isShared(): bool;

    /**
     * Add an argument to be injected.
     *
     * @param mixed $arg
     *
     * @return self
     */
    public function addArgument($arg): self;

    /**
     * Add multiple arguments to be injected.
     *
     * @param array $args
     *
     * @return self
     */
    public function addArguments(array $args): self;

    /**
     * Add a method to be invoked
     *
     * @param string $method
     * @param array  $args
     *
     * @return self
     */
    public function addMethodCall(string $method, array $args = []): self;

    /**
     * Add multiple methods to be invoked
     *
     * @param array $methods
     *
     * @return self
     */
    public function addMethodCalls(array $methods = []): self;

    /**
     * Handle instantiation and manipulation of value and return.
     *
     * @param array $args
     *
     * @return mixed
     */
    public function resolve(array $args = []);
}
