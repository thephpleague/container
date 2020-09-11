<?php

namespace League\Container\Test\Asset;

class Bar implements BarInterface
{
    protected $something;

    public function setSomething($something)
    {
        $this->something = $something;
    }
}
