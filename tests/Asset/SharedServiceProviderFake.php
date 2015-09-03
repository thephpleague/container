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
     * @param string $alias
     * @param mixed $item
     */
    public function __construct($alias, $item)
    {
        $this->alias = $alias;
        $this->item = $item;

        $this->provides[] = $alias;
    }

    public function register()
    {
        $this->getContainer()->share($this->alias, function () {
            return $this->item;
        });

        return true;
    }
}
