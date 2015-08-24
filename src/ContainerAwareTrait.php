<?php

namespace League\Container;

trait ContainerAwareTrait
{
    /**
     * @var \League\Container\ContainerInterface
     */
    protected $container;

    /**
     * Set a container.
     *
     * @param  \League\Container\ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the container.
     *
     * @return \League\Container\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
