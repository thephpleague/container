<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use League\Container\ContainerAwareInterface;

interface ServiceProviderInterface extends ContainerAwareInterface
{
    public function getIdentifier(): string;
    public function provides(string $service): bool;
    public function register(): void;
    public function setIdentifier(string $id): ServiceProviderInterface;
}
