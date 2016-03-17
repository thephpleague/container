<?php

namespace League\Container\ServiceProvider;

use League\Container\ContainerAwareInterface;

interface ServiceProviderInterface extends ContainerAwareInterface
{
    /**
     * Returns a boolean if checking whether this provider provides a specific
     * service or returns an array of provided services if no argument passed.
     *
     * @param  string $service
     * @return boolean|array
     */
    public function provides($service = null);

    /**
     * Use the register method to register items with the container via the
     * protected $this->container property or the `getContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register();

    /**
     * The signature of the service provider uniquely identifies it, so
     * that we can quickly determine if it has already been registered.
     * Defaults to get_class($provider).
     *
     * @return string
     */
    public function signature();
}
