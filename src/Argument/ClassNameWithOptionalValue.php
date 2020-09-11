<?php

namespace League\Container\Argument;

class ClassNameWithOptionalValue implements ClassNameInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var mixed
     */
    private $optionalValue;

    /**
     * Construct.
     *
     * @param string $value
     * @param mixed $optionalValue
     */
    public function __construct(string $value, $optionalValue)
    {
        $this->value = $value;
        $this->optionalValue = $optionalValue;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function getOptionalValue()
    {
        return $this->optionalValue;
    }
}
