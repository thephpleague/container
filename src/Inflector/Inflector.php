<?php

namespace League\Container\Inflector;

use League\Container\ImmutableContainerAwareTrait;
use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;

class Inflector implements ArgumentResolverInterface
{
    use ArgumentResolverTrait;
    use ImmutableContainerAwareTrait;

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Defines a method to be invoked on the subject object.
     *
     * @param  string $name
     * @param  array  $args
     * @return $this
     */
    public function invokeMethod($name, array $args)
    {
        $this->methods[$name] = $args;

        return $this;
    }

    /**
     * Defines multiple methods to be invoked on the subject object.
     *
     * @param  array $methods
     * @return $this
     */
    public function invokeMethods(array $methods)
    {
        foreach ($methods as $name => $args) {
            $this->invokeMethod($name, $args);
        }

        return $this;
    }

    /**
     * Defines a property to be set on the subject object.
     *
     * @param  string $property
     * @param  mixed  $value
     * @return $this
     */
    public function setProperty($property, $value)
    {
        $this->properties[$property] = $value;

        return $this;
    }

    /**
     * Defines multiple properties to be set on the subject object.
     *
     * @param  array $properties
     * @return $this
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        return $this;
    }

    /**
     * Apply inflections to an object.
     *
     * @param  object $object
     * @return void
     */
    public function inflect($object)
    {
        $properties = $this->resolveArguments(array_values($this->properties));
        $properties = array_combine(array_keys($this->properties), $properties);

        foreach ($properties as $property => $value) {
            $object->{$property} = $value;
        }

        foreach ($this->methods as $method => $args) {
            $args = $this->resolveArguments($args);

            call_user_func_array([$object, $method], $args);
        }
    }
}
