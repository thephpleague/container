<?php

declare(strict_types=1);

namespace League\Container\Attribute;

interface AttributeInterface
{
    public function resolve(): mixed;
}
