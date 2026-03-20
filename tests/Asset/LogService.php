<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class LogService
{
    public function __construct(public readonly CacheInterface $cache) {}
}
