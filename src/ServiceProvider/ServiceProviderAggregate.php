<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use Generator;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\ContainerException;
use LogicException;
use Override;

class ServiceProviderAggregate implements ServiceProviderAggregateInterface
{
    use ContainerAwareTrait;

    /** @var list<ServiceProviderInterface> */
    protected array $providers = [];

    /** @var list<string> */
    protected array $registered = [];

    #[Override]
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

    #[Override]
    public function provides(string $id): bool
    {
        foreach ($this as $provider) {
            if ($provider->provides($id)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function providerClassFor(string $id): string
    {
        foreach ($this as $provider) {
            if ($provider->provides($id)) {
                return $provider::class;
            }
        }

        throw new LogicException(sprintf('No provider claims to provide (%s)', $id));
    }

    /** @return Generator<int, ServiceProviderInterface> */
    #[Override]
    public function getIterator(): Generator
    {
        yield from $this->providers;
    }

    /** @return list<string> */
    #[Override]
    public function getProvidedIds(): array
    {
        $ids = [];

        foreach ($this as $provider) {
            foreach ($provider->getProvidedIds() as $id) {
                if (!in_array($id, $ids, strict: true)) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }

    #[Override]
    public function registerAll(): void
    {
        foreach ($this as $provider) {
            $this->registerProvider($provider);
        }
    }

    #[Override]
    public function register(string $service): void
    {
        if (false === $this->provides($service)) {
            throw new ContainerException(
                sprintf('(%s) is not provided by a service provider', $service),
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
