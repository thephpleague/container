<?php

namespace League\Container\ServiceProvider;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

class ServiceProviderAggregate implements ServiceProviderAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $registered = [];

    /**
     * {@inheritdoc}
     */
    public function add($provider)
    {
        if (is_string($provider) && class_exists($provider)) {
            $provider = new $provider;
        }

        if ($provider instanceof ContainerAwareInterface) {
            $provider->setContainer($this->getContainer());
        }

        if ($provider instanceof BootableServiceProviderInterface) {
            $provider->boot();
        }

        if ($provider instanceof ServiceProviderInterface) {
            foreach ($provider->provides() as $service) {
                $this->providers[$service] = $provider;
            }

            return $this;
        }

        throw new \InvalidArgumentException(
            'A service provider must be a fully qualified class name or instance ' .
            'of (\League\Container\ServiceProvider\ServiceProviderInterface)'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function provides($service)
    {
        return array_key_exists($service, $this->providers);
    }

    /**
     * {@inheritdoc}
     */
    public function register($service)
    {
        if (! array_key_exists($service, $this->providers)) {
            throw new \InvalidArgumentException(
                sprintf('(%s) is not provided by a service provider', $service)
            );
        }

        $provider  = $this->providers[$service];
        $signature = get_class($provider);

        if ($provider instanceof SignatureServiceProviderInterface) {
            $signature = $provider->getSignature();
        }

        // ensure that the provider hasn't already been invoked by any other service request
        if (in_array($signature, $this->registered)) {
            return;
        }

        $provider->register();

        $this->registered[] = $signature;
    }
}
