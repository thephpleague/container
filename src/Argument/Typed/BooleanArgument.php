<?php

declare(strict_types=1);

namespace League\Container\Argument\Typed;

use League\Container\Argument\TypedArgument;

class BooleanArgument extends TypedArgument
{
    /**
     * Construct.
     *
     * @param bool $value
     */
    public function __construct(bool $value)
    {
        parent::__construct($value, TypedArgument::TYPE_BOOL);
    }
}
