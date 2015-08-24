<?php

namespace League\Container\Argument;

interface RawArgumentInterface
{
    /**
     * Return the value of the raw argument.
     *
     * @return mixed
     */
    public function getValue();
}
