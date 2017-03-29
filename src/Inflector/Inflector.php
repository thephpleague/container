<?php declare(strict_types=1);

namespace League\Container\Inflector;

use League\Container\ContainerAwareTrait;
use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;

class Inflector implements ArgumentResolverInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Construct.
     *
     * @param string        $type
     * @param callable|null $callback
     */
    public function __construct(string $type, callable $callback = null)
    {
        $this->type     = $type;
        $this->callback = $callback;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Defines a method to be invoked on the subject object.
     *
     * @param string $name
     * @param array  $args
     *
     * @return self
     */
    public function invokeMethod(string $name, array $args): self
    {
        $this->methods[$name] = $args;

        return $this;
    }

    /**
     * Defines multiple methods to be invoked on the subject object.
     *
     * @param array $methods
     *
     * @return self
     */
    public function invokeMethods(array $methods): self
    {
        foreach ($methods as $name => $args) {
            $this->invokeMethod($name, $args);
        }

        return $this;
    }

    /**
     * Defines a property to be set on the subject object.
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return self
     */
    public function setProperty(string $property, $value): self
    {
        $this->properties[$property] = $value;

        return $this;
    }

    /**
     * Defines multiple properties to be set on the subject object.
     *
     * @param array $properties
     *
     * @return self
     */
    public function setProperties(array $properties): self
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        return $this;
    }

    /**
     * Apply inflections to an object.
     *
     * @param object $object
     *
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

        if (! is_null($this->callback)) {
            call_user_func_array($this->callback, [$object]);
        }
    }
}
