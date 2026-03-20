<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use League\Container\ContainerAwareTrait;
use Override;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    protected string $identifier;

    #[Override]
    public function getIdentifier(): string
    {
        if (empty($this->identifier)) {
            $this->identifier = $this::class;
        }

        return $this->identifier;
    }

    #[Override]
    public function getProvidedIds(): array
    {
        return [];
    }

    #[Override]
    public function setIdentifier(string $id): ServiceProviderInterface
    {
        $this->identifier = $id;
        return $this;
    }
}
