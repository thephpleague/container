<?php

declare(strict_types=1);

namespace League\Container\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    /**
     * @var array<class-string, callable[]>
     */
    protected array $listeners = [];

    /**
     * @var array<string, EventFilter[]>
     */
    protected array $filters = [];

    public function dispatch(object $event): object
    {
        if (!$event instanceof ContainerEvent) {
            return $event;
        }

        $eventType = get_class($event);

        foreach ($this->getListenersForEvent($event) as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        if (isset($this->filters[$eventType])) {
            foreach ($this->filters[$eventType] as $filter) {
                if ($event->isPropagationStopped()) {
                    break;
                }

                if ($filter->matches($event) && $filter->getListener() !== null) {
                    $listener = $filter->getListener();
                    $listener($event);
                }
            }
        }

        return $event;
    }

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = get_class($event);
        return $this->listeners[$eventClass] ?? [];
    }

    public function addListener(string $eventType, callable $listener): void
    {
        $this->listeners[$eventType][] = $listener;
    }

    public function addFilter(string $eventType, EventFilter $filter): void
    {
        $this->filters[$eventType][] = $filter;
    }

    public function listen(string $eventType): EventFilter
    {
        $filter = new EventFilter($this, $eventType);
        return $filter;
    }

    public function removeListeners(string $eventType): void
    {
        unset($this->listeners[$eventType], $this->filters[$eventType]);
    }

    public function removeListener(string $eventType, callable $listener): void
    {
        if (!isset($this->listeners[$eventType])) {
            return;
        }

        foreach ($this->listeners[$eventType] as $key => $registeredListener) {
            if ($registeredListener === $listener) {
                unset($this->listeners[$eventType][$key]);
                break;
            }
        }

        $this->listeners[$eventType] = array_values($this->listeners[$eventType]);
    }

    public function hasListenersFor(string $eventType): bool
    {
        return !empty($this->listeners[$eventType]) || !empty($this->filters[$eventType]);
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}
