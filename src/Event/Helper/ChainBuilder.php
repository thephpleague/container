<?php

declare(strict_types=1);

namespace League\Container\Event\Helper;

use League\Container\Event\EventDispatcher;

class ChainBuilder
{
    protected EventChain $chain;

    public function __construct(protected EventDispatcher $dispatcher, protected string $eventType)
    {
        $this->chain = new EventChain($dispatcher);
    }

    public function add(callable $listener, ?int $priority = null): self
    {
        $this->chain->add($listener, $priority);
        return $this;
    }

    public function when(callable $condition, callable $listener, ?int $priority = null): self
    {
        $this->chain->when($condition, $listener, $priority);
        return $this;
    }

    public function transform(callable $transformer, ?int $priority = null): self
    {
        $this->chain->transform($transformer, $priority);
        return $this;
    }

    public function register(): void
    {
        $this->chain->register($this->eventType);
    }
}
