<?php

declare(strict_types=1);

namespace League\Container\Argument\Typed;

use League\Container\Argument\TypedArgument;

class IntegerArgument extends TypedArgument
{
    /**
     * Construct.
     *
     * @param int $value
     */
    public function __construct(int $value)
    {
        parent::__construct($value, TypedArgument::TYPE_INT);
    }
}
