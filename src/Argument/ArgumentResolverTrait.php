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
            if ($arg instanceof RawArgumentInterface) {
                $arg = $arg->getValue();
                continue;
            }

            if (is_string($arg) && ($this->container->has($arg) || class_exists($arg))) {
                $arg = $this->getContainer()->get($arg);
            }
        }

        return $arguments;
    }
}
