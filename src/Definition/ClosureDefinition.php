<?php

namespace League\Container\Definition;

use League\Container\ContainerInterface;
use League\Container\Exception;

class ClosureDefinition extends AbstractDefinition implements DefinitionInterface
{
    /**
     * @var \Closure
     */
    protected $closure;

    /**
     * Constructor
     *
     * @param string                      $alias
     * @param \Closure                    $closure
     * @param \League\Container\ContainerInterface $container
     */
    public function __construct($alias, \Closure $closure, ContainerInterface $container)
    {
        parent::__construct($alias, $container);

        $this->closure = $closure;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $args = [])
    {
        return call_user_func_array($this->closure, $this->resolveArguments($args));
    }
}
