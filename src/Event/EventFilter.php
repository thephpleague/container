<?php

declare(strict_types=1);

namespace League\Container\Event;

use Closure;

class EventFilter
{
    /** @var array<int, string> */
    protected array $typeFilters = [];

    /** @var array<int, string> */
    protected array $tagFilters = [];

    /** @var array<int, string> */
    protected array $idFilters = [];

    /** @var array<int, Closure> */
    protected array $customFilters = [];

    /**
     * @var callable|null
     */
    protected mixed $listener = null;

    public function __construct(protected EventDispatcher $dispatcher, protected string $eventType) {}

    public function forType(string ...$types): self
    {
        array_push($this->typeFilters, ...$types);
        return $this;
    }

    public function forTag(string ...$tags): self
    {
        array_push($this->tagFilters, ...$tags);
        return $this;
    }

    public function forId(string ...$ids): self
    {
        array_push($this->idFilters, ...$ids);
        return $this;
    }

    public function where(Closure $filter): self
    {
        $this->customFilters[] = $filter;
        return $this;
    }

    public function matches(ContainerEvent $event): bool
    {
        if (!empty($this->idFilters) && !in_array($event->getId(), $this->idFilters, true)) {
            return false;
        }

        if (!empty($this->tagFilters)) {
            $hasMatchingTag = false;
            foreach ($this->tagFilters as $tag) {
                if ($event->hasTag($tag)) {
                    $hasMatchingTag = true;
                    break;
                }
            }
            if (!$hasMatchingTag) {
                return false;
            }
        }

        if (!empty($this->typeFilters)) {
            if (!$event instanceof ServiceResolvedEvent) {
                return false;
            }
            $hasMatchingType = false;
            foreach ($this->typeFilters as $type) {
                if ($event->isInstanceOf($type)) {
                    $hasMatchingType = true;
                    break;
                }
            }
            if (!$hasMatchingType) {
                return false;
            }
        }

        foreach ($this->customFilters as $customFilter) {
            if (!$customFilter($event)) {
                return false;
            }
        }

        return true;
    }

    public function __invoke(ContainerEvent $event): void
    {
        if ($this->matches($event) && $this->listener !== null) {
            ($this->listener)($event);
        }
    }

    public function then(callable $listener): void
    {
        $this->listener = $listener;
        $this->dispatcher->addFilter($this->eventType, $this);
    }

    public function getListener(): ?callable
    {
        return $this->listener;
    }
}
