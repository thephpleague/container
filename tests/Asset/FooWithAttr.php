<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

use League\Container\Attribute\Inject;

class FooWithAttr extends Foo
{
    public function __construct(#[Inject(Bar::class)] Bar $bar)
    {
        parent::__construct($bar);
    }
}
