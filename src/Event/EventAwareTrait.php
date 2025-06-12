<?php

declare(strict_types=1);

namespace League\Container\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

trait EventAwareTrait
{
    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function listen(string $eventType, callable $listener): EventFilter
    {
        if (!$this->eventDispatcher instanceof EventDispatcher) {
            throw new RuntimeException(sprintf(
                'Event dispatcher must be an instance of %s to use listen() method',
                EventDispatcher::class
            ));
        }

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
        if (!$this->eventDispatcher instanceof EventDispatcher) {
            throw new RuntimeException(sprintf(
                'Event dispatcher must be an instance of %s to use addListener() method',
                EventDispatcher::class
            ));
        }

        $this->eventDispatcher->addListener($eventType, $listener);
    }
}
