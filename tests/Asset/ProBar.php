<?php

namespace League\Container\Test\Asset;

class ProBar implements BarInterface
{
    protected function __construct()
    {
    }

    public static function factory()
    {
        return new self;
    }
}
