<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class CycleA
{
    public function __construct(public readonly CycleB $b) {}
}
