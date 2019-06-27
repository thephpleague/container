<?php declare(strict_types=1);

namespace League\Container;

interface ContainerAwareInterface
{
    /**
     * Set a container
     *
     * @param Container $container
     *
     * @return ContainerAwareInterface
     */
    public function setContainer(Container $container): ContainerAwareInterface;

    /**
     * Get the container
     *
     * @return Container
     */
    public function getContainer(): Container;
}
