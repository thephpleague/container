<?php declare(strict_types=1);

namespace League\Container;

use League\Container\Exception\ContainerException;
use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /**
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * Set a container.
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return self
     */
    public function setContainer(ContainerInterface $container) : ContainerAwareInterface
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the container.
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer() : ContainerInterface
    {
        if ($this->container instanceof ContainerInterface) {
            return $this->container;
        }

        throw new ContainerException('No container implementation has been set.');
    }
}
