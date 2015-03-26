<?php

namespace League\Container\Definition;

class CallableDefinition extends AbstractDefinition implements DefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(array $args = [])
    {
        $args     = (empty($args)) ? $this->arguments : $args;
        $resolved = $this->resolveArguments($args);

        if (is_array($this->concrete) && is_string($this->concrete[0])) {
            $this->concrete[0] = ($this->container->has($this->concrete[0]))
                               ? $this->container->get($this->concrete[0])
                               : $this->concrete[0];
        }

        return call_user_func_array($this->concrete, $resolved);
    }
}
