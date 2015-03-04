<?php

namespace League\Container;

trait ArgumentResolverTrait
{
    /**
     * Uses the container to resolve arguments
     *
     * @param  array $args
     * @return array
     */
    public function resolveArguments(array $args)
    {
        $resolved = [];

        foreach ($args as $arg) {
            $resolved[] = (
                is_string($arg) && (
                    isset($this->getContainer()[$arg]) ||
                    class_exists($arg)
                )
            ) ? $this->getContainer()->get($arg) : $arg;
        }

        return $resolved;
    }

    /**
     * Ensure that ContainerAwareTrait is implemented
     *
     * @return \League\Container\ContainerInterface
     */
    abstract public function getContainer();
}
