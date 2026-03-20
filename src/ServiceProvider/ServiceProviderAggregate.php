<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use Generator;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\ContainerException;

class ServiceProviderAggregate implements ServiceProviderAggregateInterface
{
    use ContainerAwareTrait;

    /** @var list<ServiceProviderInterface> */
    protected array $providers = [];

    /** @var list<string> */
    protected array $registered = [];

    #[\Override]
    public function add(ServiceProviderInterface $provider): ServiceProviderAggregateInterface
    {
        if (in_array($provider, $this->providers, true)) {
            return $this;
        }

        $provider->setContainer($this->getContainer());

        if ($provider instanceof BootableServiceProviderInterface) {
            $provider->boot();
        }

        $this->providers[] = $provider;
        return $this;
    }

    #[\Override]
    public function provides(string $id): bool
    {
        foreach ($this as $provider) {
            if ($provider->provides($id)) {
                return true;
            }
        }

        return false;
    }

    /** @return Generator<int, ServiceProviderInterface> */
    #[\Override]
    public function getIterator(): Generator
    {
        yield from $this->providers;
    }

    #[\Override]
    public function registerAll(): void
    {
        foreach ($this as $provider) {
            $this->registerProvider($provider);
        }
    }

    #[\Override]
    public function register(string $service): void
    {
        if (false === $this->provides($service)) {
            throw new ContainerException(
                sprintf('(%s) is not provided by a service provider', $service)
            );
        }

        foreach ($this as $provider) {
            if ($provider->provides($service)) {
                $this->registerProvider($provider);
            }
        }
    }

    private function registerProvider(ServiceProviderInterface $provider): void
    {
        if (in_array($provider->getIdentifier(), $this->registered, true)) {
            return;
        }

        $provider->register();
        $this->registered[] = $provider->getIdentifier();
    }
}
