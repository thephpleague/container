<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use IteratorAggregate;
use League\Container\ContainerAwareInterface;

interface ServiceProviderAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add($provider): ServiceProviderAggregateInterface;
    public function provides(string $service): bool;
    public function register(string $service): void;
}
