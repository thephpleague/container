<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\Event\BeforeResolveEvent;
use League\Container\Event\DefinitionResolvedEvent;
use League\Container\Event\EventDispatcher;
use League\Container\Event\EventFilter;
use League\Container\Event\OnDefineEvent;
use League\Container\Event\ServiceResolvedEvent;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;

beforeEach(function () {
    $this->dispatcher = new EventDispatcher();
    $this->container = new Container();
    $this->container->setEventDispatcher($this->dispatcher);
});

test('on define event is dispatched', function () {
    $eventFired = false;
    $capturedEvent = null;

    $this->dispatcher->addListener(OnDefineEvent::class, function (OnDefineEvent $event) use (&$eventFired, &$capturedEvent) {
        $eventFired = true;
        $capturedEvent = $event;
    });

    $this->container->add(Foo::class);

    expect($eventFired)->toBeTrue();
    expect($capturedEvent)->toBeInstanceOf(OnDefineEvent::class);
    expect($capturedEvent->getId())->toBe(Foo::class);
    expect($capturedEvent->getDefinition())->not->toBeNull();
});

test('before resolve event is dispatched', function () {
    $eventFired = false;
    $capturedEvent = null;

    $this->dispatcher->addListener(BeforeResolveEvent::class, function (BeforeResolveEvent $event) use (&$eventFired, &$capturedEvent) {
        $eventFired = true;
        $capturedEvent = $event;
    });

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($eventFired)->toBeTrue();
    expect($capturedEvent)->toBeInstanceOf(BeforeResolveEvent::class);
    expect($capturedEvent->getId())->toBe(Foo::class);
});

test('service resolved event is dispatched', function () {
    $eventFired = false;
    $capturedEvent = null;

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$eventFired, &$capturedEvent) {
        $eventFired = true;
        $capturedEvent = $event;
    });

    $this->container->add(Foo::class);
    $resolvedObject = $this->container->get(Foo::class);

    expect($eventFired)->toBeTrue();
    expect($capturedEvent)->toBeInstanceOf(ServiceResolvedEvent::class);
    expect($capturedEvent->getId())->toBe(Foo::class);
    expect($capturedEvent->getResolved())->toBe($resolvedObject);
    expect($capturedEvent->isInstanceOf(Foo::class))->toBeTrue();
});

test('event can modify resolved object', function () {
    $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
        if ($event->isInstanceOf(Foo::class)) {
            $foo = $event->getResolved();
            $foo->modified = true;
            $event->setResolved($foo);
        }
    });

    $this->container->add(Foo::class);
    $foo = $this->container->get(Foo::class);

    expect(property_exists($foo, 'modified'))->toBeTrue();
    expect($foo->modified)->toBeTrue();
});

test('event filter for type', function () {
    $fooEventFired = false;
    $barEventFired = false;

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$fooEventFired) {
        $fooEventFired = true;
    })->forType(Foo::class);

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$barEventFired) {
        $barEventFired = true;
    })->forType(Bar::class);

    $this->container->add(Foo::class);
    $this->container->add(Bar::class);

    $this->container->get(Foo::class);
    expect($fooEventFired)->toBeTrue();
    expect($barEventFired)->toBeFalse();

    $this->container->get(Bar::class);
    expect($barEventFired)->toBeTrue();
});

test('event filter for tag', function () {
    $taggedEventFired = false;
    $untaggedEventFired = false;

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$taggedEventFired) {
        $taggedEventFired = true;
    })->forTag('shared');

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$untaggedEventFired) {
        $untaggedEventFired = true;
    })->forTag('custom');

    $this->container->addShared(Foo::class);
    $this->container->add(Bar::class);

    $this->container->get(Foo::class);
    expect($taggedEventFired)->toBeTrue();
    expect($untaggedEventFired)->toBeFalse();
});

test('event filter for id', function () {
    $specificEventFired = false;
    $otherEventFired = false;

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$specificEventFired) {
        $specificEventFired = true;
    })->forId(Foo::class);

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$otherEventFired) {
        $otherEventFired = true;
    })->forId(Bar::class);

    $this->container->add(Foo::class);
    $this->container->add(Bar::class);

    $this->container->get(Foo::class);
    expect($specificEventFired)->toBeTrue();
    expect($otherEventFired)->toBeFalse();
});

test('custom event filter', function () {
    $customEventFired = false;

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$customEventFired) {
        $customEventFired = true;
    })->where(function (ServiceResolvedEvent $event) {
        return $event->getId() === Foo::class && $event->isInstanceOf(Foo::class);
    });

    $this->container->add(Foo::class);
    $this->container->add(Bar::class);

    $this->container->get(Foo::class);
    expect($customEventFired)->toBeTrue();

    $customEventFired = false;
    $this->container->get(Bar::class);
    expect($customEventFired)->toBeFalse();
});

test('event propagation can be stopped', function () {
    $firstListenerFired = false;
    $secondListenerFired = false;

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$firstListenerFired) {
        $firstListenerFired = true;
        $event->stopPropagation();
    });

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function () use (&$secondListenerFired) {
        $secondListenerFired = true;
    });

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($firstListenerFired)->toBeTrue();
    expect($secondListenerFired)->toBeFalse();
});

test('early resolution in before resolve event', function () {
    $customObject = new Foo();
    $customObject->isCustom = true;

    $this->dispatcher->addListener(BeforeResolveEvent::class, function (BeforeResolveEvent $event) use ($customObject) {
        if ($event->getId() === Foo::class) {
            $event->setResolved($customObject);
        }
    });

    $this->container->add(Foo::class);
    $resolved = $this->container->get(Foo::class);

    expect($resolved)->toBe($customObject);
    expect($resolved->isCustom)->toBeTrue();
});

test('definition resolved event is dispatched', function () {
    $capturedEvent = null;

    $this->dispatcher->addListener(DefinitionResolvedEvent::class, function (DefinitionResolvedEvent $event) use (&$capturedEvent) {
        $capturedEvent = $event;
    });

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($capturedEvent)->toBeInstanceOf(DefinitionResolvedEvent::class);
    expect($capturedEvent->getId())->toBe(Foo::class);
    expect($capturedEvent->getDefinition())->not->toBeNull();
    expect($capturedEvent->getTags())->toBeArray();
});

test('definition resolved event can short circuit resolution', function () {
    $customFoo = new Foo();

    $this->dispatcher->addListener(DefinitionResolvedEvent::class, function (DefinitionResolvedEvent $event) use ($customFoo) {
        $event->setResolved($customFoo);
    });

    $this->container->add(Foo::class);
    $resolved = $this->container->get(Foo::class);

    expect($resolved)->toBe($customFoo);
});

test('for type on non service resolved event returns false', function () {
    $listenerFired = false;

    $this->dispatcher->listen(BeforeResolveEvent::class)->forType(Foo::class)->then(function () use (&$listenerFired) {
        $listenerFired = true;
    });

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($listenerFired)->toBeFalse();
});

test('stop propagation in listener prevents filters from executing', function () {
    $directListenerFired = false;
    $filterListenerFired = false;

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$directListenerFired) {
        $directListenerFired = true;
        $event->stopPropagation();
    });

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$filterListenerFired) {
        $filterListenerFired = true;
    });

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($directListenerFired)->toBeTrue();
    expect($filterListenerFired)->toBeFalse();
});

test('early resolution with null value', function () {
    $this->dispatcher->addListener(BeforeResolveEvent::class, function (BeforeResolveEvent $event) {
        if ($event->getId() === Foo::class) {
            $event->setResolved(null);
        }
    });

    $this->container->add(Foo::class);
    $resolved = $this->container->get(Foo::class);

    expect($resolved)->toBeNull();
});

test('set event dispatcher accepts concrete dispatcher', function () {
    $newDispatcher = new EventDispatcher();
    $this->container->setEventDispatcher($newDispatcher);

    expect($this->container->getEventDispatcher())->toBe($newDispatcher);
});

test('remove listener removes specific listener', function () {
    $firstListenerFired = false;
    $secondListenerFired = false;

    $firstListener = function () use (&$firstListenerFired) {
        $firstListenerFired = true;
    };

    $secondListener = function () use (&$secondListenerFired) {
        $secondListenerFired = true;
    };

    $this->dispatcher->addListener(ServiceResolvedEvent::class, $firstListener);
    $this->dispatcher->addListener(ServiceResolvedEvent::class, $secondListener);
    $this->dispatcher->removeListener(ServiceResolvedEvent::class, $firstListener);

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($firstListenerFired)->toBeFalse();
    expect($secondListenerFired)->toBeTrue();
});

test('remove listeners clears listeners and filters', function () {
    $directListenerFired = false;
    $filterListenerFired = false;

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function () use (&$directListenerFired) {
        $directListenerFired = true;
    });

    $this->dispatcher->listen(ServiceResolvedEvent::class)->then(function () use (&$filterListenerFired) {
        $filterListenerFired = true;
    });

    $this->dispatcher->removeListeners(ServiceResolvedEvent::class);

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($directListenerFired)->toBeFalse();
    expect($filterListenerFired)->toBeFalse();
});

test('get new dispatches events with new flag', function () {
    $capturedBeforeEvent = null;
    $capturedServiceEvent = null;

    $this->dispatcher->addListener(BeforeResolveEvent::class, function (BeforeResolveEvent $event) use (&$capturedBeforeEvent) {
        $capturedBeforeEvent = $event;
    });

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$capturedServiceEvent) {
        $capturedServiceEvent = $event;
    });

    $this->container->add(Foo::class);
    $this->container->getNew(Foo::class);

    expect($capturedBeforeEvent->isNew())->toBeTrue();
    expect($capturedServiceEvent->isNew())->toBeTrue();
});

test('tagged resolution dispatches service resolved per service', function () {
    $collectedEvents = [];

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$collectedEvents) {
        $collectedEvents[] = $event;
    });

    $this->container->add(Foo::class)->addTag('my-group');
    $this->container->add(Bar::class)->addTag('my-group');
    $this->container->get('my-group');

    expect($collectedEvents)->toHaveCount(2);
});

test('where composes multiple closures with and', function () {
    $listenerFiredCount = 0;

    $this->container->listen(ServiceResolvedEvent::class, function () use (&$listenerFiredCount) {
        $listenerFiredCount++;
    })
        ->where(fn($e) => $e->getId() === Foo::class)
        ->where(fn($e) => $e instanceof ServiceResolvedEvent && $e->isInstanceOf(Foo::class));

    $this->container->add(Foo::class);
    $this->container->add(Bar::class);

    $this->container->get(Foo::class);
    $this->container->get(Bar::class);

    expect($listenerFiredCount)->toBe(1);
});

test('is new flag is correctly set on service resolved event', function () {
    $capturedEvent = null;

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$capturedEvent) {
        $capturedEvent = $event;
    });

    $this->container->addShared(Foo::class);

    $this->container->get(Foo::class);
    expect($capturedEvent)->toBeInstanceOf(ServiceResolvedEvent::class);
    expect($capturedEvent->isNew())->toBeFalse();

    $capturedEvent = null;

    $this->container->get(Foo::class);
    expect($capturedEvent)->toBeInstanceOf(ServiceResolvedEvent::class);
    expect($capturedEvent->isNew())->toBeFalse();
});

test('resolve with no listeners does not crash and returns correct object', function () {
    $container = new Container();
    $container->setEventDispatcher(new EventDispatcher());

    $container->add(Foo::class);
    $resolved = $container->get(Foo::class);

    expect($resolved)->toBeInstanceOf(Foo::class);
});

test('has listeners for returns false when empty', function () {
    $dispatcher = new EventDispatcher();

    expect($dispatcher->hasListenersFor(ServiceResolvedEvent::class))->toBeFalse();
    expect($dispatcher->hasListenersFor(BeforeResolveEvent::class))->toBeFalse();
});

test('has listeners for returns true for direct listener', function () {
    $dispatcher = new EventDispatcher();
    $dispatcher->addListener(ServiceResolvedEvent::class, fn() => null);

    expect($dispatcher->hasListenersFor(ServiceResolvedEvent::class))->toBeTrue();
    expect($dispatcher->hasListenersFor(BeforeResolveEvent::class))->toBeFalse();
});

test('has listeners for returns true for filter', function () {
    $dispatcher = new EventDispatcher();
    $dispatcher->listen(ServiceResolvedEvent::class)->then(fn() => null);

    expect($dispatcher->hasListenersFor(ServiceResolvedEvent::class))->toBeTrue();
});

test('has listeners for returns false after remove listeners', function () {
    $dispatcher = new EventDispatcher();
    $dispatcher->addListener(ServiceResolvedEvent::class, fn() => null);

    expect($dispatcher->hasListenersFor(ServiceResolvedEvent::class))->toBeTrue();

    $dispatcher->removeListeners(ServiceResolvedEvent::class);

    expect($dispatcher->hasListenersFor(ServiceResolvedEvent::class))->toBeFalse();
});

test('before resolve event skipped when no listeners registered for it', function () {
    $serviceEventFired = false;
    $beforeEventFired = false;

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function () use (&$serviceEventFired) {
        $serviceEventFired = true;
    });

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($serviceEventFired)->toBeTrue();
    expect($beforeEventFired)->toBeFalse();

    $serviceEventFired = false;
    $this->dispatcher->addListener(BeforeResolveEvent::class, function () use (&$beforeEventFired) {
        $beforeEventFired = true;
    });

    $this->container->get(Foo::class);

    expect($serviceEventFired)->toBeTrue();
    expect($beforeEventFired)->toBeTrue();
});

test('after resolve callback receives resolved object', function () {
    $received = null;

    $this->container->add(Foo::class);
    $this->container->afterResolve(Foo::class, function ($obj) use (&$received) {
        $received = $obj;
    });

    $resolved = $this->container->get(Foo::class);

    expect($received)->toBe($resolved);
    expect($received)->not->toBeInstanceOf(ServiceResolvedEvent::class);
});

test('after resolve filters by type', function () {
    $fooCount = 0;
    $barCount = 0;

    $this->container->afterResolve(Foo::class, function () use (&$fooCount) {
        $fooCount++;
    });

    $this->container->afterResolve(Bar::class, function () use (&$barCount) {
        $barCount++;
    });

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($fooCount)->toBe(1);
    expect($barCount)->toBe(0);

    $this->container->add(Bar::class);
    $this->container->get(Bar::class);

    expect($barCount)->toBe(1);
});

test('after resolve returns event filter for chaining', function () {
    $filter = $this->container->afterResolve(Foo::class, fn() => null);

    expect($filter)->toBeInstanceOf(EventFilter::class);
    $filter->forTag('shared');
});

test('after resolve works alongside direct listeners', function () {
    $directFired = false;
    $afterResolveFired = false;

    $this->dispatcher->addListener(ServiceResolvedEvent::class, function () use (&$directFired) {
        $directFired = true;
    });

    $this->container->afterResolve(Foo::class, function () use (&$afterResolveFired) {
        $afterResolveFired = true;
    });

    $this->container->add(Foo::class);
    $this->container->get(Foo::class);

    expect($directFired)->toBeTrue();
    expect($afterResolveFired)->toBeTrue();
});
