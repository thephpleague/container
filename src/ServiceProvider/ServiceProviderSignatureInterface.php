<?php

namespace League\Container\ServiceProvider;

use League\Container\ContainerAwareInterface;

interface ServiceProviderSignatureInterface
{
    /**
     * The signature of the service provider uniquely identifies it, so
     * that we can quickly determine if it has already been registered.
     * Defaults to get_class($provider).
     *
     * @return string
     */
    public function signature();
}
