<?php

namespace League\Container\Test\Asset;

use League\Container\ServiceProvider\AbstractSignatureServiceProvider;
use League\Container\ServiceProvider\SignatureServiceProviderInterface;

class SharedServiceProviderWithSignatureFake extends AbstractSignatureServiceProvider implements SignatureServiceProviderInterface
{
    /**
     * @var string
     */
    protected $alias;

    /**
     * @var mixed
     */
    protected $item;

    /**
     * @param string $alias
     * @param mixed  $item
     * @param string $signature
     */
    public function __construct($alias, $item, $signature)
    {
        $this->alias = $alias;
        $this->item = $item;

        $this->provides[] = $alias;

        $this->withSignature($signature);
    }

    public function register()
    {
        $this->getContainer()->share($this->alias, function () {
            return $this->item;
        });

        return true;
    }
}
