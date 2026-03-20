<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use IteratorAggregate;
use League\Container\ContainerAwareInterface;

/** @extends IteratorAggregate<int, ServiceProviderInterface> */
interface ServiceProviderAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add(ServiceProviderInterface $provider): ServiceProviderAggregateInterface;
    public function provides(string $id): bool;
    public function providerClassFor(string $id): string;

    /** @return list<string> */
    public function getProvidedIds(): array;

    public function register(string $service): void;
    public function registerAll(): void;
}
