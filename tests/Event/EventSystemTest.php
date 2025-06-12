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
}
