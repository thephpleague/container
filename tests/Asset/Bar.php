<?php

namespace League\Container\Test\Asset;

class Bar
{
    public $baz;

    public function __construct(BazInterface $baz)
    {
        $this->baz = $baz;
    }
}
