<?php

namespace League\Container;

use ArrayAccess;
use League\Container\Definition\CallableDefinition;
use League\Container\Definition\ClassDefinition;
use League\Container\Definition\DefinitionInterface;
use League\Container\Definition\FactoryInterface;

class Container implements ContainerInterface, ArrayAccess
{
    /**
     * @var array
     */
    protected $callables = [];

    /**
     * @var \League\Container\Definition\FactoryInterface
     */
    protected $factory;

    /**
     * @var array
     */
    protected $inflectors = [];

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var array - \League\Container\ServiceProvider[]
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $singletons = [];

    /**
     * Constructor
     *
     * @param array|ArrayAccess                             $config
     * @param \League\Container\Definition\FactoryInterface $factory
     */
    public function __construct($config = [], FactoryInterface $factory = null)
    {
        $this->factory = (is_null($factory)) ? new Definition\Factory : $factory;

        $this->addItemsFromConfig($config);

        $this->add('League\Container\ContainerInterface', $this);
        $this->add('League\Container\Container', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function add($alias, $concrete = null, $singleton = false)
    {
        if (is_null($concrete)) {
            $concrete = $alias;
        }

        // are we dealing with a service provider?
        if ($concrete instanceof ServiceProvider) {
            return $this->addServiceProvider($concrete);
        }

        // if the concrete is an already instantiated object, we just store it
        // as a singleton
        if ((is_object($concrete) && ! $concrete instanceof \Closure)) {
            $this->singletons[$alias] = $concrete;
            return null;
        }

        // we need to build a definition
        $factory    = $this->getDefinitionFactory();
        $definition = $factory($alias, $concrete, $this);

        if ($definition instanceof DefinitionInterface) {
            $this->items[$alias] = [
                'definition' => $definition,
                'singleton'  => (boolean) $singleton
            ];

            return $definition;
        }

        // dealing with an arbitrary value so just store as singleton
        $this->singletons[$alias] = $concrete;
    }

    /**
     * {@inheritdoc}
     */
    public function addServiceProvider($provider)
    {
        if ((! is_string($provider)) && (! $provider instanceof ServiceProvider)) {
            throw new \InvalidArgumentException(
                'When registering a service provider, you must provide either and instance of ' .
                '[\League\Container\ServiceProvider] or a fully qualified class name'
            );
        }

        $this->providers[] = $provider;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function singleton($alias, $concrete = null)
    {
        return $this->add($alias, $concrete, true);
    }

    /**
     * {@inheritdoc}
     */
    public function inflector($type, callable $callback = null)
    {
        if (is_null($callback)) {
            $inflector = new Inflector;
            $this->inflectors[$type] = $inflector;

            return $inflector;
        }

        $this->inflectors[$type] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function invokable($alias, callable $concrete = null)
    {
        if (is_null($concrete)) {
            $concrete = $alias;
        }

        if (is_string($concrete) && strpos($concrete, '::') !== false) {
            $concrete = explode('::', $concrete);
        }

        if (! is_callable($concrete)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot register callable attached to alias [%s]', $alias)
            );
        }

        $factory = $this->getDefinitionFactory();
        $definition = $factory($alias, $concrete, $this, true);

        $this->callables[$alias] = $definition;

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function extend($alias)
    {
        if (! $this->isRegistered($alias) && ! $this->isInServiceProvider($alias)) {
            throw new \InvalidArgumentException(sprintf('[%s] is not registered in the container.', $alias));
        }

        if ($this->isSingleton($alias)) {
            throw new Exception\ServiceNotExtendableException(sprintf(
                '[%s] is being managed as a singleton and cannot be modified.',
                $alias
            ));
        }

        return $this->items[$alias]['definition'];
    }

    /**
     * {@inheritdoc}
     */
    public function get($alias, array $args = [])
    {
        // if we have a singleton just return it
        if (array_key_exists($alias, $this->singletons)) {
            return $this->applyInflectors($this->singletons[$alias]);
        }

        // invoke the correct definition
        if (array_key_exists($alias, $this->items) && array_key_exists('definition', $this->items[$alias])) {
            return $this->applyInflectors($this->resolveDefinition($alias, $args));
        }

        // is the definition registered via a service provider?
        if ($this->isInServiceProvider($alias)) {
            return $this->get($alias, $args);
        }

        // if we've got this far, we can assume we need to reflect on a class
        // and automatically resolve it's dependencies, we also cache the
        // result if a caching adapter is available
        $definition = $this->reflect($alias);

        $this->items[$alias]['definition'] = $definition;

        return $this->applyInflectors($definition($args));
    }

    /**
     * {@inheritdoc}
     */
    public function call($alias, array $args = [])
    {
        if (is_string($alias) && array_key_exists($alias, $this->callables)) {
            $definition = $this->callables[$alias];

            return $definition($args);
        }

        if (is_callable($alias)) {
            $callable = $this->reflectCallable($alias);
            $args     = $this->resolveCallableArguments($callable, $args);

            return call_user_func_array($alias, $args);
        }

        throw new \RuntimeException(
            sprintf('Unable to call callable [%s], does it exist and is it registered with the container?', $alias)
        );
    }

    /**
     * Resolve a container definition
     *
     * @param  string $alias
     * @param  array  $args
     * @return mixed
     */
    protected function resolveDefinition($alias, array $args)
    {
        $definition = $this->items[$alias]['definition'];
        $return     = $definition;

        if ($definition instanceof CallableDefinition || $definition instanceof ClassDefinition) {
            $return = $definition($args);
        }

        // store as a singleton if needed
        if (isset($this->items[$alias]['singleton']) && $this->items[$alias]['singleton'] === true) {
            $this->singletons[$alias] = $return;
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function isRegistered($alias)
    {
        return array_key_exists($alias, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleton($alias)
    {
        return (
            array_key_exists($alias, $this->singletons) ||
            (array_key_exists($alias, $this->items) && $this->items[$alias]['singleton'] === true)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isInServiceProvider($alias)
    {
        foreach ($this->providers as &$provider) {
            if (! $provider instanceof ServiceProvider) {
                $provider = new $provider;
            }

            $provider->setContainer($this);

            if ($provider->provides($alias)) {
                $provider->register();
                return true;
            }
        }

        return false;
    }

    /**
     * Encapsulate the definition factory to allow for invokation
     *
     * @return \League\Container\Definition\Factory
     */
    protected function getDefinitionFactory()
    {
        return $this->factory;
    }

    /**
     * Populate the container with items from config
     *
     * @param $config array|ArrayAccess
     * @return void
     */
    protected function addItemsFromConfig($config)
    {
        if (! is_array($config) && ! $config instanceof \ArrayAccess) {
            throw new \InvalidArgumentException(
                'You can only load definitions from an array or an object that implements ArrayAccess.'
            );
        }

        if (empty($config)) {
            return null;
        }

        if (! isset($config['di']) || ! is_array($config['di'])) {
            throw new \RuntimeException(
                'Could not process configuration, either the top level key [di] is missing or the configuration is not an array.'
            );
        }

        $definitions = $config['di'];

        array_walk($definitions, [$this, 'createDefinitionFromConfig']);
    }

    /**
     * Create a definition from a config entry
     *
     * @param  mixed  $options
     * @param  string $alias
     * @return void
     */
    protected function createDefinitionFromConfig($options, $alias)
    {
        $concrete  = $this->resolveConcreteClassFromConfig($options);
        $singleton = false;
        $arguments = [];
        $methods   = [];

        if (is_array($options)) {
            $singleton = (! empty($options['singleton']));
            $arguments = (array_key_exists('arguments', $options)) ? (array) $options['arguments'] : [];
            $methods   = (array_key_exists('methods', $options)) ? (array) $options['methods'] : [];
        }

        // define in the container, with constructor arguments and method calls
        $definition = $this->add($alias, $concrete, $singleton);

        if ($definition instanceof DefinitionInterface) {
            $definition->withArguments($arguments);
        }

        if ($definition instanceof ClassDefinition) {
            $definition->withMethodCalls($methods);
        }
    }

    /**
     * Resolves the concrete class
     *
     * @param mixed $concrete
     * @return mixed
     */
    protected function resolveConcreteClassFromConfig($concrete)
    {
        if (is_array($concrete)) {
            if (array_key_exists('definition', $concrete)) {
                $concrete = $concrete['definition'];
            } elseif (array_key_exists('class', $concrete)) {
                $concrete = $concrete['class'];
            }
        }

        // if the concrete doesn't have a class associated with it then it
        // must be either a Closure or arbitrary type so we just bind that
        return $concrete;
    }

    /**
     * Reflect on a class, establish it's dependencies and build a definition
     * from that information
     *
     * @param  string $class
     * @throws Exception\ReflectionException
     * @throws Exception\UnresolvableDependencyException
     * @return \League\Container\Definition\ClassDefinition
     */
    protected function reflect($class)
    {
        // try to reflect on the class so we can build a definition
        try {
            $reflection  = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
        } catch (\ReflectionException $e) {
            throw new Exception\ReflectionException(
                sprintf('Unable to reflect on the class [%s], does the class exist and is it properly autoloaded?', $class)
            );
        }

        $factory = $this->getDefinitionFactory();
        $definition = $factory($class, $class, $this);

        if (is_null($constructor)) {
            return $definition;
        }

        // loop through dependencies and get aliases/values
        foreach ($constructor->getParameters() as $param) {
            $dependency = $param->getClass();

            // if the dependency is not a class we attempt to get a dafult value
            if (is_null($dependency)) {
                if ($param->isDefaultValueAvailable()) {
                    $definition->withArgument($param->getDefaultValue());
                    continue;
                }

                throw new Exception\UnresolvableDependencyException(
                    sprintf('Unable to resolve a non-class dependency of [%s] for [%s]', $param, $class)
                );
            }

            // if the dependency is a class, just register it's name as an
            // argument with the definition
            $definition->withArgument($dependency->getName());
        }

        return $definition;
    }

    /**
     * Get a reflection object for this callable.
     *
     * @param  callable $callable
     * @return ReflectionFunctionAbstract
     */
    protected function reflectCallable(callable $callable)
    {
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable)) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        } else {
            return new \ReflectionFunction($callable);
        }
    }

    /**
     * Resolves arguments for a callable
     *
     * @param  \ReflectionFunctionAbstract $reflector
     * @param  array $args
     * @return array
     */
    protected function resolveCallableArguments(\ReflectionFunctionAbstract $reflector, $args = [])
    {
        return array_map(function (\ReflectionParameter $parameter) use ($args) {
            $name  = $parameter->name;
            $class = $parameter->getClass();

            if (isset($args[$name])) {
                return $args[$name];
            }

            if ($class) {
                return $this->get($class->name);
            }

            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new \RuntimeException(
                sprintf(
                    'Cannot resolve argument [%s], it should be provided within an array of arguments passed to ' .
                    '[%s::call], have a default value or be type hinted',
                    $name, get_class($this)
                )
            );
        }, $reflector->getParameters());
    }

    /**
     * Apply any active inflectors to the resolved object
     *
     * @param  object $object
     * @return object
     */
    protected function applyInflectors($object)
    {
        foreach ($this->inflectors as $type => $inflector) {
            if (! $object instanceof $type) {
                continue;
            }

            if ($inflector instanceof Inflector) {
                $inflector->setContainer($this);
                $inflector->inflect($object);
                continue;
            }

            // must be dealing with a callable as the inflector
            call_user_func_array($inflector, [$object]);
        }

        return $object;
    }

    /**
     * Array Access get
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Array Access set
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->singleton($key, $value);
    }

    /**
     * Array Access unset
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
        unset($this->singletons[$key]);
    }

    /**
     * Array Access isset
     *
     * @param  string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return (
            $this->isRegistered($key) ||
            $this->isSingleton($key)  ||
            $this->isInServiceProvider($key)
        );
    }
}
