<?php

declare(strict_types=1);

namespace League\Container\Argument;

interface ArgumentInterface
{
    public function getValue(): mixed;
}
