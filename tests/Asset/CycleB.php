<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class CycleB
{
    public function __construct(public readonly CycleA $a) {}
}
