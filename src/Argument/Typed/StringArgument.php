<?php

declare(strict_types=1);

namespace League\Container\Argument\Typed;

use League\Container\Argument\TypedArgument;

class StringArgument extends TypedArgument
{
    /**
     * Construct.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        parent::__construct($value, TypedArgument::TYPE_STRING);
    }
}
