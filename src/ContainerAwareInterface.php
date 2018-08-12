<?php declare(strict_types=1);

namespace League\Container;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * Set a container
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return self
     */
    public function setContainer(ContainerInterface $container) : self;

    /**
     * Get the container
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer() : ContainerInterface;
}
