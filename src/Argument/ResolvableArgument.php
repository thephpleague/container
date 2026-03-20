<?php

declare(strict_types=1);

namespace League\Container\Argument;

use Override;

class ResolvableArgument implements ResolvableArgumentInterface
{
    public function __construct(protected string $value) {}

    #[Override]
    public function getValue(): string
    {
        return $this->value;
    }
}
