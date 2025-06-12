<?php

declare(strict_types=1);

namespace League\Container\Event\Helper;

use League\Container\Event\ContainerEvent;
use League\Container\Event\DefinitionResolvedEvent;
use League\Container\Event\EventDispatcher;
use League\Container\Event\ServiceResolvedEvent;

class EventChain
{
    protected array $chain = [];
    protected int $defaultPriority = 100;

    public function __construct(protected EventDispatcher $dispatcher)
    {
    }

    public function add(callable $listener, ?int $priority = null): self
    {
        $this->chain[] = [
            'listener' => $listener,
            'priority' => $priority ?? $this->defaultPriority--,
        ];

        return $this;
    }

    public function when(callable $condition, callable $listener, ?int $priority = null): self
    {
        $conditionalListener = function (ContainerEvent $event) use ($condition, $listener) {
            if ($condition($event)) {
                $listener($event);
            }
        };

        return $this->add($conditionalListener, $priority);
    }

    public function transform(callable $transformer, ?int $priority = null): self
    {
        $transformListener = function (ContainerEvent $event) use ($transformer) {
            if ($event instanceof ServiceResolvedEvent || $event instanceof DefinitionResolvedEvent) {
                $resolved = $event->getResolved();
                $transformed = $transformer($resolved, $event);
                if ($transformed !== null) {
                    $event->setResolved($transformed);
                }
            }
        };

        return $this->add($transformListener, $priority);
    }

    public function register(string $eventType): void
    {
        usort($this->chain, fn($a, $b) => $b['priority'] <=> $a['priority']);

        $chainListener = function (ContainerEvent $event) {
            foreach ($this->chain as $item) {
                if ($event->isPropagationStopped()) {
                    break;
                }
                $item['listener']($event);
            }
        };

        $this->dispatcher->addListener($eventType, $chainListener);
    }

    public static function create(EventDispatcher $dispatcher): self
    {
        return new self($dispatcher);
    }

    public static function for(EventDispatcher $dispatcher, string $eventType): ChainBuilder
    {
        return new ChainBuilder($dispatcher, $eventType);
    }
}
