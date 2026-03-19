<?php

declare(strict_types=1);

namespace League\Container\Test\Event;

use League\Container\Container;
use League\Container\Event\BeforeResolveEvent;
use League\Container\Event\DefinitionResolvedEvent;
use League\Container\Event\EventDispatcher;
use League\Container\Event\EventFilter;
use League\Container\Event\OnDefineEvent;
use League\Container\Event\ServiceResolvedEvent;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;
use PHPUnit\Framework\TestCase;

class EventSystemTest extends TestCase
{
    protected Container $container;
    protected EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->container = new Container();
        $this->container->setEventDispatcher($this->dispatcher);
    }

    public function testOnDefineEventIsDispatched(): void
    {
        $eventFired = false;
        $capturedEvent = null;

        $this->dispatcher->addListener(OnDefineEvent::class, function (OnDefineEvent $event) use (&$eventFired, &$capturedEvent) {
            $eventFired = true;
            $capturedEvent = $event;
        });

        $this->container->add(Foo::class);

        $this->assertTrue($eventFired);
        $this->assertInstanceOf(OnDefineEvent::class, $capturedEvent);
        $this->assertSame(Foo::class, $capturedEvent->getId());
        $this->assertNotNull($capturedEvent->getDefinition());
    }

    public function testBeforeResolveEventIsDispatched(): void
    {
        $eventFired = false;
        $capturedEvent = null;

        $this->dispatcher->addListener(BeforeResolveEvent::class, function (BeforeResolveEvent $event) use (&$eventFired, &$capturedEvent) {
            $eventFired = true;
            $capturedEvent = $event;
        });

        $this->container->add(Foo::class);
        $this->container->get(Foo::class);

        $this->assertTrue($eventFired);
        $this->assertInstanceOf(BeforeResolveEvent::class, $capturedEvent);
        $this->assertSame(Foo::class, $capturedEvent->getId());
    }

    public function testServiceResolvedEventIsDispatched(): void
    {
        $eventFired = false;
        $capturedEvent = null;

        $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$eventFired, &$capturedEvent) {
            $eventFired = true;
            $capturedEvent = $event;
        });

        $this->container->add(Foo::class);
        $resolvedObject = $this->container->get(Foo::class);

        $this->assertTrue($eventFired);
        $this->assertInstanceOf(ServiceResolvedEvent::class, $capturedEvent);
        $this->assertSame(Foo::class, $capturedEvent->getId());
        $this->assertSame($resolvedObject, $capturedEvent->getResolved());
        $this->assertTrue($capturedEvent->isInstanceOf(Foo::class));
    }

    public function testEventCanModifyResolvedObject(): void
    {
        $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) {
            if ($event->isInstanceOf(Foo::class)) {
                $foo = $event->getResolved();
                $foo->modified = true;
                $event->setResolved($foo);
            }
        });

        $this->container->add(Foo::class);
        $foo = $this->container->get(Foo::class);

        $this->assertTrue(property_exists($foo, 'modified'));
        $this->assertTrue($foo->modified);
    }

    public function testEventFilterForType(): void
    {
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
        $this->assertTrue($fooEventFired);
        $this->assertFalse($barEventFired);

        $this->container->get(Bar::class);
        $this->assertTrue($barEventFired);
    }

    public function testEventFilterForTag(): void
    {
        $taggedEventFired = false;
        $untaggedEventFired = false;

        $this->container->listen(ServiceResolvedEvent::class, function () use (&$taggedEventFired) {
            $taggedEventFired = true;
        })->forTag('shared');

        $this->container->listen(ServiceResolvedEvent::class, function () use (&$untaggedEventFired) {
            $untaggedEventFired = true;
        })->forTag('custom');

        $this->container->addShared(Foo::class); // This will have 'shared' tag
        $this->container->add(Bar::class);       // This won't have 'shared' tag

        $this->container->get(Foo::class);
        $this->assertTrue($taggedEventFired);
        $this->assertFalse($untaggedEventFired);
    }

    public function testEventFilterForId(): void
    {
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
        $this->assertTrue($specificEventFired);
        $this->assertFalse($otherEventFired);
    }

    public function testCustomEventFilter(): void
    {
        $customEventFired = false;

        $this->container->listen(ServiceResolvedEvent::class, function () use (&$customEventFired) {
            $customEventFired = true;
        })->where(function (ServiceResolvedEvent $event) {
            return $event->getId() === Foo::class && $event->isInstanceOf(Foo::class);
        });

        $this->container->add(Foo::class);
        $this->container->add(Bar::class);

        $this->container->get(Foo::class);
        $this->assertTrue($customEventFired);

        $customEventFired = false;
        $this->container->get(Bar::class);
        $this->assertFalse($customEventFired);
    }

    public function testEventPropagationCanBeStopped(): void
    {
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

        $this->assertTrue($firstListenerFired);
        $this->assertFalse($secondListenerFired);
    }

    public function testEarlyResolutionInBeforeResolveEvent(): void
    {
        $customObject = new Foo();
        $customObject->isCustom = true;

        $this->dispatcher->addListener(BeforeResolveEvent::class, function (BeforeResolveEvent $event) use ($customObject) {
            if ($event->getId() === Foo::class) {
                $event->setResolved($customObject);
            }
        });

        $this->container->add(Foo::class);
        $resolved = $this->container->get(Foo::class);

        $this->assertSame($customObject, $resolved);
        $this->assertTrue($resolved->isCustom);
    }

    public function testDefinitionResolvedEventIsDispatched(): void
    {
        $capturedEvent = null;

        $this->dispatcher->addListener(DefinitionResolvedEvent::class, function (DefinitionResolvedEvent $event) use (&$capturedEvent) {
            $capturedEvent = $event;
        });

        $this->container->add(Foo::class);
        $this->container->get(Foo::class);

        $this->assertInstanceOf(DefinitionResolvedEvent::class, $capturedEvent);
        $this->assertSame(Foo::class, $capturedEvent->getId());
        $this->assertNotNull($capturedEvent->getDefinition());
        $this->assertIsArray($capturedEvent->getTags());
    }

    public function testDefinitionResolvedEventCanShortCircuitResolution(): void
    {
        $customFoo = new Foo();

        $this->dispatcher->addListener(DefinitionResolvedEvent::class, function (DefinitionResolvedEvent $event) use ($customFoo) {
            $event->setResolved($customFoo);
        });

        $this->container->add(Foo::class);
        $resolved = $this->container->get(Foo::class);

        $this->assertSame($customFoo, $resolved);
    }

    public function testForTypeOnNonServiceResolvedEventReturnsFalse(): void
    {
        $listenerFired = false;

        $this->dispatcher->listen(BeforeResolveEvent::class)->forType(Foo::class)->then(function () use (&$listenerFired) {
            $listenerFired = true;
        });

        $this->container->add(Foo::class);
        $this->container->get(Foo::class);

        $this->assertFalse($listenerFired);
    }

    public function testStopPropagationInListenerPreventsFiltersFromExecuting(): void
    {
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

        $this->assertTrue($directListenerFired);
        $this->assertFalse($filterListenerFired);
    }

    public function testEarlyResolutionWithNullValue(): void
    {
        $this->dispatcher->addListener(BeforeResolveEvent::class, function (BeforeResolveEvent $event) {
            if ($event->getId() === Foo::class) {
                $event->setResolved(null);
            }
        });

        $this->container->add(Foo::class);
        $resolved = $this->container->get(Foo::class);

        $this->assertNull($resolved);
    }

    public function testSetEventDispatcherAcceptsConcreteDispatcher(): void
    {
        $newDispatcher = new EventDispatcher();
        $this->container->setEventDispatcher($newDispatcher);

        $this->assertSame($newDispatcher, $this->container->getEventDispatcher());
    }

    public function testRemoveListenerRemovesSpecificListener(): void
    {
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

        $this->assertFalse($firstListenerFired);
        $this->assertTrue($secondListenerFired);
    }

    public function testRemoveListenersClearsListenersAndFilters(): void
    {
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

        $this->assertFalse($directListenerFired);
        $this->assertFalse($filterListenerFired);
    }

    public function testGetNewDispatchesEventsWithNewFlag(): void
    {
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

        $this->assertTrue($capturedBeforeEvent->isNew());
        $this->assertTrue($capturedServiceEvent->isNew());
    }

    public function testTaggedResolutionDispatchesServiceResolvedPerService(): void
    {
        $collectedEvents = [];

        $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$collectedEvents) {
            $collectedEvents[] = $event;
        });

        $this->container->add(Foo::class)->addTag('my-group');
        $this->container->add(Bar::class)->addTag('my-group');
        $this->container->get('my-group');

        $this->assertCount(2, $collectedEvents);
    }

    public function testWhereComposesMultipleClosuresWithAnd(): void
    {
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

        $this->assertSame(1, $listenerFiredCount);
    }

    public function testIsNewFlagIsCorrectlySetOnServiceResolvedEvent(): void
    {
        $capturedEvent = null;

        $this->dispatcher->addListener(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use (&$capturedEvent) {
            $capturedEvent = $event;
        });

        $this->container->addShared(Foo::class);

        $this->container->get(Foo::class);
        $this->assertInstanceOf(ServiceResolvedEvent::class, $capturedEvent);
        $this->assertFalse($capturedEvent->isNew());

        $capturedEvent = null;

        $this->container->get(Foo::class);
        $this->assertInstanceOf(ServiceResolvedEvent::class, $capturedEvent);
        $this->assertFalse($capturedEvent->isNew());
    }

    public function testResolveWithNoListenersDoesNotCrashAndReturnsCorrectObject(): void
    {
        $container = new Container();
        $container->setEventDispatcher(new EventDispatcher());

        $container->add(Foo::class);
        $resolved = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $resolved);
    }

    public function testHasListenersForReturnsFalseWhenEmpty(): void
    {
        $dispatcher = new EventDispatcher();

        $this->assertFalse($dispatcher->hasListenersFor(ServiceResolvedEvent::class));
        $this->assertFalse($dispatcher->hasListenersFor(BeforeResolveEvent::class));
    }

    public function testHasListenersForReturnsTrueForDirectListener(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ServiceResolvedEvent::class, fn() => null);

        $this->assertTrue($dispatcher->hasListenersFor(ServiceResolvedEvent::class));
        $this->assertFalse($dispatcher->hasListenersFor(BeforeResolveEvent::class));
    }

    public function testHasListenersForReturnsTrueForFilter(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->listen(ServiceResolvedEvent::class)->then(fn() => null);

        $this->assertTrue($dispatcher->hasListenersFor(ServiceResolvedEvent::class));
    }

    public function testHasListenersForReturnsFalseAfterRemoveListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ServiceResolvedEvent::class, fn() => null);

        $this->assertTrue($dispatcher->hasListenersFor(ServiceResolvedEvent::class));

        $dispatcher->removeListeners(ServiceResolvedEvent::class);

        $this->assertFalse($dispatcher->hasListenersFor(ServiceResolvedEvent::class));
    }

    public function testBeforeResolveEventSkippedWhenNoListenersRegisteredForIt(): void
    {
        $serviceEventFired = false;
        $beforeEventFired = false;

        $this->dispatcher->addListener(ServiceResolvedEvent::class, function () use (&$serviceEventFired) {
            $serviceEventFired = true;
        });

        $this->container->add(Foo::class);
        $this->container->get(Foo::class);

        $this->assertTrue($serviceEventFired);
        $this->assertFalse($beforeEventFired);

        $serviceEventFired = false;
        $this->dispatcher->addListener(BeforeResolveEvent::class, function () use (&$beforeEventFired) {
            $beforeEventFired = true;
        });

        $this->container->get(Foo::class);

        $this->assertTrue($serviceEventFired);
        $this->assertTrue($beforeEventFired);
    }

    public function testAfterResolveCallbackReceivesResolvedObject(): void
    {
        $received = null;

        $this->container->add(Foo::class);
        $this->container->afterResolve(Foo::class, function ($obj) use (&$received) {
            $received = $obj;
        });

        $resolved = $this->container->get(Foo::class);

        $this->assertSame($resolved, $received);
        $this->assertNotInstanceOf(ServiceResolvedEvent::class, $received);
    }

    public function testAfterResolveFiltersByType(): void
    {
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

        $this->assertSame(1, $fooCount);
        $this->assertSame(0, $barCount);

        $this->container->add(Bar::class);
        $this->container->get(Bar::class);

        $this->assertSame(1, $barCount);
    }

    public function testAfterResolveReturnsEventFilterForChaining(): void
    {
        $filter = $this->container->afterResolve(Foo::class, fn() => null);

        $this->assertInstanceOf(EventFilter::class, $filter);
        $filter->forTag('shared');
    }

    public function testAfterResolveWorksAlongsideDirectListeners(): void
    {
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

        $this->assertTrue($directFired);
        $this->assertTrue($afterResolveFired);
    }
}
