<?php

namespace League\Container\Test\Asset;

class FooWithScalarResolvedDependency
{
    public $stringVal;
    public $arrayVal;
    public $integerVal;
    public $booleanVal;
    public $nullVal;

    public function __construct($stringVal, array $arrayVal, $integerVal, $booleanVal, $nullVal)
    {
        $this->stringVal  = $stringVal;
        $this->arrayVal   = $arrayVal;
        $this->integerVal = $integerVal;
        $this->booleanVal = $booleanVal;
        $this->nullVal = $nullVal;
    }
}
