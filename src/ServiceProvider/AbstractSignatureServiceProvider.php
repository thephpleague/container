<?php

namespace League\Container\ServiceProvider;

abstract class AbstractSignatureServiceProvider
    extends AbstractServiceProvider
    implements SignatureServiceProviderInterface
{
    /**
     * @var string
     */
    protected $signature;

    /**
     * {@inheritdoc}
     */
    public function withSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSignature()
    {
        return (is_null($this->signature)) ? get_class($this) : $this->signature;
    }
}
