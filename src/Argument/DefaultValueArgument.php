<?php

declare(strict_types=1);

namespace League\Container\Argument;

use Override;

class DefaultValueArgument extends ResolvableArgument implements DefaultValueInterface
{
    public function __construct(string $value, protected mixed $defaultValue = null)
    {
        parent::__construct($value);
    }

    #[Override]
    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }
}
