<?php

namespace League\Container;

interface ContainerAwareInterface
{
    /**
     * Set a container
     *
     * @param \League\Container\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container);

    /**
     * Get the container
     *
     * @return \League\Container\ContainerInterface
     */
    public function getContainer();
}
