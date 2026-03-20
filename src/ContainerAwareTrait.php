<?php

declare(strict_types=1);

namespace League\Container;

use BadMethodCallException;
use League\Container\Exception\ContainerException;
use Override;

trait ContainerAwareTrait
{
    /**
     * @var ?DefinitionContainerInterface
     */
    protected ?DefinitionContainerInterface $container = null;

    #[Override]
    public function setContainer(DefinitionContainerInterface $container): static
    {
        $this->container = $container;

        if ($this instanceof ContainerAwareInterface) {
            return $this;
        }

        throw new BadMethodCallException(sprintf(
            'Attempt to use (%s) while not implementing (%s)',
            ContainerAwareTrait::class,
            ContainerAwareInterface::class,
        ));
    }

    #[Override]
    public function getContainer(): DefinitionContainerInterface
    {
        if ($this->container instanceof DefinitionContainerInterface) {
            return $this->container;
        }

        throw new ContainerException('No container implementation has been set.');
    }
}
