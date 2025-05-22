<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use League\Container\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    protected string $identifier;

    public function getIdentifier(): string
    {
        if (empty($this->identifier)) {
            $this->identifier = get_class($this);
        }

        return $this->identifier;
    }

    public function setIdentifier(string $id): ServiceProviderInterface
    {
        $this->identifier = $id;
        return $this;
    }
}
