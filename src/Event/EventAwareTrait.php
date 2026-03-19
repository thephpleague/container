<?php

declare(strict_types=1);

namespace League\Container\Event;

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
        $filter = $this->eventDispatcher->listen($eventType);
        $filter->then($listener);
        return $filter;
    }

    protected function dispatchEvent(ContainerEvent $event): ContainerEvent
    {
        if ($this->eventDispatcher) {
            return $this->eventDispatcher->dispatch($event);
        }

        return $event;
    }

    public function addListener(string $eventType, callable $listener): void
    {
        $this->eventDispatcher->addListener($eventType, $listener);
    }
}
