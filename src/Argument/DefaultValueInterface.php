<?php

declare(strict_types=1);

namespace League\Container\Argument;

interface DefaultValueInterface extends ArgumentInterface
{
    public function getDefaultValue(): mixed;
}
