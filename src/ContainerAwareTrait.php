<?php declare(strict_types=1);

namespace League\Container;

use League\Container\Exception\ContainerException;

trait ContainerAwareTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Set a container.
     *
     * @param Container $container
     *
     * @return ContainerAwareInterface
     */
    public function setContainer(Container $container): ContainerAwareInterface
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the container.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        if ($this->container instanceof Container) {
            return $this->container;
        }

        throw new ContainerException('No container implementation has been set.');
    }
}
