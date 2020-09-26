<?php

declare(strict_types=1);

namespace League\Container\Argument\Typed;

use League\Container\Argument\TypedArgument;

class CallableArgument extends TypedArgument
{
    /**
     * Construct.
     *
     * @param callable $value
     */
    public function __construct(callable $value)
    {
        parent::__construct($value, TypedArgument::TYPE_CALLABLE);
    }
}
