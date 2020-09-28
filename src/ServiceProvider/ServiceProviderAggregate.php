<?php

declare(strict_types=1);

namespace League\Container\ServiceProvider;

use Generator;
use League\Container\Exception\ContainerException;
use League\Container\{ContainerAwareInterface, ContainerAwareTrait};

class ServiceProviderAggregate implements ServiceProviderAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var ServiceProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $registered = [];

    public function add($provider): ServiceProviderAggregateInterface
    {
        if (is_string($provider) && $this->getContainer()->has($provider)) {
            $provider = $this->getContainer()->get($provider);
        } elseif (is_string($provider) && class_exists($provider)) {
            $provider = new $provider();
        }

        if (in_array($provider, $this->providers, true)) {
            return $this;
        }

        if ($provider instanceof ContainerAwareInterface) {
            $provider->setContainer($this->getContainer());
        }

        if ($provider instanceof BootableServiceProviderInterface) {
            $provider->boot();
        }

        if ($provider instanceof ServiceProviderInterface) {
            $this->providers[] = $provider;

            return $this;
        }

        throw new ContainerException(sprintf(
            'A service provider must be a fully qualified class name or instance of (%s) ',
            ServiceProviderInterface::class
        ));
    }

    public function provides(string $service): bool
    {
        foreach ($this->getIterator() as $provider) {
            if ($provider->provides($service)) {
                return true;
            }
        }

        return false;
    }

    public function getIterator(): Generator
    {
        yield from $this->providers;
    }

    public function register(string $service): void
    {
        if (false === $this->provides($service)) {
            throw new ContainerException(
                sprintf('(%s) is not provided by a service provider', $service)
            );
        }

        foreach ($this->getIterator() as $provider) {
            if (in_array($provider->getIdentifier(), $this->registered, true)) {
                continue;
            }

            if ($provider->provides($service)) {
                $provider->register();
                $this->registered[] = $provider->getIdentifier();
            }
        }
    }
}
