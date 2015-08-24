<?php

namespace League\Container;

interface ImmutableContainerAwareInterface
{
    /**
     * Set a container
     *
     * @param \League\Container\ImmutableContainerInterface $container
     */
    public function setContainer(ImmutableContainerInterface $container);

    /**
     * Get the container
     *
     * @return \League\Container\ImmutableContainerInterface
     */
    public function getContainer();
}
