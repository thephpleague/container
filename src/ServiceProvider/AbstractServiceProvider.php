<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use League\Container\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $provides = [];

    /**
     * @var string
     */
    protected $identifier;

    public function getIdentifier(): string
    {
        return $this->identifier ?? get_class($this);
    }

    public function provides(string $alias): bool
    {
        return in_array($alias, $this->provides, true);
    }

    public function setIdentifier(string $id): ServiceProviderInterface
    {
        $this->identifier = $id;
        return $this;
    }
}
