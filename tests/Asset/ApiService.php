<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class ApiService
{
    public function __construct(public readonly CacheInterface $cache) {}
}
