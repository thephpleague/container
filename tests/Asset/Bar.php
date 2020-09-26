<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class Bar
{
    protected $something;

    public function setSomething($something): void
    {
        $this->something = $something;
    }
}
