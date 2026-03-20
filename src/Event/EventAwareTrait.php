<?php

declare(strict_types=1);

namespace League\Container\Event;

use League\Container\Exception\ContainerException;

trait EventAwareTrait
{
    protected ?EventDispatcher $eventDispatcher = null;

    public function getEventDispatcher(): ?EventDispatcher
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(?EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function listen(string $eventType, callable $listener): EventFilter
    {
        if ($this->eventDispatcher === null) {
            throw new ContainerException('No event dispatcher has been configured.');
        }

        $filter = $this->eventDispatcher->listen($eventType);
        $filter->then($listener);
        return $filter;
    }

    protected function dispatchEvent(ContainerEvent $event): ContainerEvent
    {
        $this->eventDispatcher?->dispatch($event);
        return $event;
    }

    public function addListener(string $eventType, callable $listener): void
    {
        if ($this->eventDispatcher === null) {
            throw new ContainerException('No event dispatcher has been configured.');
        }

        $this->eventDispatcher->addListener($eventType, $listener);
    }
}
