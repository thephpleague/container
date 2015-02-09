<?php

namespace League\Container;

class Inflector implements ContainerAwareInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Defines a method to be invoked on the subject object
     *
     * @param  string $name
     * @param  array  $args
     * @return \League\Container\Inflector
     */
    public function invokeMethod($name, array $args)
    {
        $this->methods[$name] = $args;

        return $this;
    }

    /**
     * Defines multiple methods to be invoked on the subject object
     *
     * @param  array $methods
     * @return \League\Container\Inflector
     */
    public function invokeMethods(array $methods)
    {
        foreach ($methods as $name => $args) {
            $this->invokeMethod($name, $args);
        }

        return $this;
    }

    /**
     * Defines a property to be set on the subject object
     *
     * @param string $property
     * @param mixed  $value
     * @return \League\Container\Inflector
     */
    public function setProperty($property, $value)
    {
        $this->properties[$property] = $value;

        return $this;
    }

    /**
     * Defines multiple properties to be set on the subject object
     *
     * @param array $properties
     * @return \League\Container\Inflector
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        return $this;
    }

    /**
     * Apply inflections to an object
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

        foreach ($this->methods as $name => $args) {
            $args = $this->resolveArguments($args);

            call_user_func_array([$object, $name], $args);
        }
    }
}
