<?php

namespace League\Container\Argument;

use League\Container\ImmutableContainerAwareInterface;

interface ArgumentResolverInterface extends ImmutableContainerAwareInterface
{
    /**
     * Resolve an array of arguments to their concrete implementations.
     *
     * @param  array $arguments
     * @return array
     */
    public function resolveArguments(array $arguments);
}
