<?php

declare(strict_types=1);

namespace League\Container;

interface ContainerAwareInterface
{
    /**
     * Set a container
     *
     * @param DefinitionContainerInterface $container
     *
     * @return self
     */
    public function setContainer(DefinitionContainerInterface $container): self;

    /**
     * Get the container
     *
     * @return DefinitionContainerInterface
     */
    public function getContainer(): DefinitionContainerInterface;
}
