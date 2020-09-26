<?php

declare(strict_types=1);

namespace League\Container\Argument\Typed;

use League\Container\Argument\TypedArgument;

class ArrayArgument extends TypedArgument
{
    /**
     * Construct.
     *
     * @param array $value
     */
    public function __construct(array $value)
    {
        parent::__construct($value, TypedArgument::TYPE_ARRAY);
    }
}
