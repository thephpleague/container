<?php

namespace League\Container\ServiceProvider;

interface SignatureServiceProviderInterface
{
    /**
     * Set a custom signature for the service provider. This enables
     * registering the same service provider multiple times.
     *
     * @param  string $signature
     * @return self
     */
    public function withSignature($signature);

    /**
     * The signature of the service provider uniquely identifies it, so
     * that we can quickly determine if it has already been registered.
     * Defaults to get_class($provider).
     *
     * @return string
     */
    public function getSignature();
}
