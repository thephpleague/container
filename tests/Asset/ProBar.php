<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class ProBar implements BarInterface
{
    protected function __construct() {}

    public static function factory(): ProBar
    {
        return new self();
    }
}
