<?php

namespace League\Container\Test\Asset;

use League\Container\ServiceProvider\AbstractServiceProvider;

class LiarServiceProvider extends AbstractServiceProvider 
{
    /**
     * @var array
     */
    protected $provides = [
        'lie'
    ];
    
    public function register()
    {
    }
}
