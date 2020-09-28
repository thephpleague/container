<?php

declare(strict_types=1);

namespace League\Container;

use League\Container\Definition\DefinitionInterface;
use League\Container\Inflector\InflectorInterface;
use Psr\Container\ContainerInterface;

interface DefinitionContainerInterface extends ContainerInterface
{
    public function add(string $id, $concrete = null): DefinitionInterface;
    public function addServiceProvider($provider): self;
    public function addShared(string $id, $concrete = null): DefinitionInterface;
    public function extend(string $id): DefinitionInterface;
    public function inflector(string $type, callable $callback = null): InflectorInterface;
}
