<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

use League\Container\Attribute\Resolve;

class FooWithResolveAttr
{
    public function __construct(#[Resolve(Bar::class, 'getSomething')] public readonly mixed $value) {}
}
