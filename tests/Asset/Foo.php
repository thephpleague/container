<?php

namespace League\Container\Test\Asset;

class Foo
{
    public $bar;

    public $baz;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function injectBaz(BazInterface $baz)
    {
        $this->baz = $baz;
    }
}
