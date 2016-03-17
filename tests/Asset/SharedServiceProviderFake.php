<?php

namespace League\Container\Test\Asset;

use League\Container\ServiceProvider\AbstractServiceProvider;

class SharedServiceProviderFake extends AbstractServiceProvider
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
    public function __construct($alias, $item, $signature = false)
    {
        $this->alias = $alias;
        $this->item = $item;

        $this->provides[] = $alias;
        $this->signature = $signature ?: get_class($this);
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
