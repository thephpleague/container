<?php

namespace League\Container;

trait ImmutableContainerAwareTrait
{
    /**
     * @var \League\Container\ImmutableContainerInterface
     */
    protected $container;

    /**
     * Set a container.
     *
     * @param  \League\Container\ImmutableContainerInterface $container
     * @return mixed
     */
    public function setContainer(ImmutableContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the container.
     *
     * @return \League\Container\ImmutableContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
