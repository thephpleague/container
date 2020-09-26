<?php

declare(strict_types=1);

namespace League\Container\Argument\Typed;

use League\Container\Argument\TypedArgument;

class FloatArgument extends TypedArgument
{
    /**
     * Construct.
     *
     * @param float $value
     */
    public function __construct(float $value)
    {
        parent::__construct($value, TypedArgument::TYPE_FLOAT);
    }
}
