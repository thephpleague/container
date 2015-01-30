<?php

namespace League\Container;

abstract class ServiceProvider implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var boolean
     */
    protected $singleton = false;

    /**
     * Resolve a service. Access to the container can be achieved via the protected $this->container attribute
     * or the `getContainer` method from the ContainerAwareTrait.
     *
     * @return mixed
     */
    abstract public function resolve();

    /**
     * Sets a name/alias for the service provider.
     *
     * @param  string $name
     * @return \League\Container\ServiceProvider
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name/alias of the service provider.
     *
     * @return string
     */
    public function getName()
    {
        if (is_null($this->name)) {
            throw new Exception\NoServiceProviderNameException(sprintf(
                'Service Provider [%s] must have a [$name] property or call the [setName] method', get_called_class()
            ));
        }

        return $this->name;
    }

    /**
     * Set the status of the service provide on whether the result of
     * a `resolve` call should be stored as a singleton by the container.
     *
     * @param  boolean $trigger
     * @return \League\Container\ServiceProvider
     */
    public function storeAsSingleton($trigger = true)
    {
        $this->singleton = (boolean) $trigger;

        return $this;
    }

    /**
     * Get the status of the service provider on whether the result of
     * a `resolve` call should be stored as a singleton by the container.
     *
     * @return boolean
     */
    public function isSingleton()
    {
        return $this->singleton;
    }
}
