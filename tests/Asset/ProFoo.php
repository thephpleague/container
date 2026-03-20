<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class ProFoo
{
    public ?ProBar $bar;

    public function __construct(?ProBar $bar = null)
    {
        $this->bar = $bar;
    }
}
