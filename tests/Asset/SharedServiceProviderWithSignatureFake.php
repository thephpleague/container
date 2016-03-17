<?php

namespace League\Container\Test\Asset;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\ServiceProviderSignatureInterface;

class SharedServiceProviderWithSignatureFake extends AbstractServiceProvider implements ServiceProviderSignatureInterface
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @var mixed
     */
    private $item;

    /**
     * @var string
     */
    private $signature;

    /**
     * @param string $alias
     * @param mixed $item
     */
    public function __construct($alias, $item, $signature)
    {
        $this->alias = $alias;
        $this->item = $item;

        $this->provides[] = $alias;
        $this->signature = $signature;
    }

    public function register()
    {
        $this->getContainer()->share($this->alias, function () {
            return $this->item;
        });

        return true;
    }

    public function signature()
    {
        return $this->signature;
    }
}
