<?php

namespace League\Container\Test\Asset;

class FooWithScalarDependency
{
    public $stringVal;
    public $arrayVal;
    public $integerVal;
    public $booleanVal;

    public function __construct($stringVal, array $arrayVal, $integerVal, $booleanVal)
    {
        $this->stringVal  = $stringVal;
        $this->arrayVal   = $arrayVal;
        $this->integerVal = $integerVal;
        $this->booleanVal = $booleanVal;
    }
}
