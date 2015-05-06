<?php

namespace League\Container\Test\Asset;

class Foo
{
    public $bar;

    public function __construct(Bar $bar = null)
    {
        $this->bar = $bar;
    }

    public function setBar(Bar $bar)
    {
        $this->bar = $bar;
    }
}
