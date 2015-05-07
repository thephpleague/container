<?php

namespace League\Container\Argument;

trait ArgumentResolverTrait
{
    /**
     * {@inheritdoc}
     */
    public function resolveArguments(array $arguments)
    {
        foreach ($arguments as &$arg) {
            $arg = (is_string($arg) && ($this->container->has($arg) || class_exists($arg)))
                 ? $this->container->get($arg)
                 : $arg;
        }

        return $arguments;
    }
}
