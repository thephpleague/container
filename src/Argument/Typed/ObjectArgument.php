<?php

declare(strict_types=1);

namespace League\Container\Argument\Typed;

use League\Container\Argument\TypedArgument;

class ObjectArgument extends TypedArgument
{
    /**
     * Construct.
     *
     * @param object $value
     */
    public function __construct(object $value)
    {
        parent::__construct($value, TypedArgument::TYPE_OBJECT);
    }
}
