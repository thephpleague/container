<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class Bar implements BarInterface
{
    protected $something;

    public function setSomething($something): void
    {
        $this->something = $something;
    }
}
