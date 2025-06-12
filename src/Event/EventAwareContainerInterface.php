<?php

declare(strict_types=1);

namespace League\Container\Event;

use Psr\EventDispatcher\EventDispatcherInterface;

interface EventAwareContainerInterface
{
    public function getEventDispatcher(): ?EventDispatcherInterface;
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void;
    public function listen(string $eventType, callable $listener): EventFilter;
    public function addListener(string $eventType, callable $listener): void;
}
