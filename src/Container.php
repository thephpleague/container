<?php

declare(strict_types=1);

namespace League\Container;

use League\Container\Definition\DefinitionAggregate;
use League\Container\Definition\DefinitionAggregateInterface;
use League\Container\Definition\DefinitionInterface;
use League\Container\Event\BeforeResolveEvent;
use League\Container\Event\DefinitionResolvedEvent;
use League\Container\Event\EventAwareTrait;
use League\Container\Event\EventDispatcher;
use League\Container\Event\EventFilter;
use League\Container\Event\OnDefineEvent;
use League\Container\Event\ServiceResolvedEvent;
use League\Container\Exception\ContainerException;
use League\Container\Exception\NotFoundException;
use League\Container\Inflector\InflectorAggregate;
use League\Container\Inflector\InflectorAggregateInterface;
use League\Container\Inflector\InflectorInterface;
use League\Container\ServiceProvider\ServiceProviderAggregate;
use League\Container\ServiceProvider\ServiceProviderAggregateInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements DefinitionContainerInterface
{
    use EventAwareTrait;

    /**
     * @var ContainerInterface[]
     */
    protected array $delegates = [];

    public function __construct(
        protected DefinitionAggregateInterface $definitions = new DefinitionAggregate(),
        protected ServiceProviderAggregateInterface $providers = new ServiceProviderAggregate(),
        protected InflectorAggregateInterface $inflectors = new InflectorAggregate(),
        protected bool $defaultToShared = false,
        protected bool $defaultToOverwrite = false,
    ) {
        $this->definitions->setContainer($this);
        $this->providers->setContainer($this);
        $this->inflectors->setContainer($this);

        $this->eventDispatcher = new EventDispatcher();
    }

    public function add(string $id, mixed $concrete = null, bool $overwrite = false): DefinitionInterface
    {
        $toOverwrite = $this->defaultToOverwrite || $overwrite;
        $concrete ??= $id;

        if (true === $this->defaultToShared) {
            return $this->addShared($id, $concrete, $toOverwrite);
        }

        $definition = $this->definitions->add($id, $concrete, $toOverwrite);

        if ($this->eventDispatcher->hasListenersFor(OnDefineEvent::class)) {
            $tags = $this->getDefinitionTags($definition);
            $event = new OnDefineEvent($id, $definition, $tags);
            $this->dispatchEvent($event);
            return $event->getDefinition();
        }

        return $definition;
    }

    public function addShared(string $id, mixed $concrete = null, bool $overwrite = false): DefinitionInterface
    {
        $toOverwrite = $this->defaultToOverwrite || $overwrite;
        $concrete ??= $id;
        $definition = $this->definitions->addShared($id, $concrete, $toOverwrite);
        $definition->addTag('shared');

        if ($this->eventDispatcher->hasListenersFor(OnDefineEvent::class)) {
            $tags = $this->getDefinitionTags($definition);
            $event = new OnDefineEvent($id, $definition, $tags);
            $this->dispatchEvent($event);
            return $event->getDefinition();
        }

        return $definition;
    }

    public function defaultToShared(bool $shared = true): ContainerInterface
    {
        $this->defaultToShared = $shared;
        return $this;
    }

    public function defaultToOverwrite(bool $overwrite = true): ContainerInterface
    {
        $this->defaultToOverwrite = $overwrite;
        return $this;
    }

    public function extend(string $id): DefinitionInterface
    {
        if ($this->providers->provides($id)) {
            $this->providers->register($id);
        }

        if ($this->definitions->has($id)) {
            return $this->definitions->getDefinition($id);
        }

        throw new NotFoundException(sprintf(
            'Unable to extend alias (%s) as it is not being managed as a definition',
            $id
        ));
    }

    public function addServiceProvider(ServiceProviderInterface $provider): DefinitionContainerInterface
    {
        $this->providers->add($provider);
        return $this;
    }

    public function get(string $id)
    {
        return $this->resolve($id);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getNew(string $id): mixed
    {
        return $this->resolve($id, true);
    }

    public function has(string $id): bool
    {
        if ($this->definitions->has($id)) {
            return true;
        }

        if ($this->definitions->hasTag($id)) {
            return true;
        }

        if ($this->providers->provides($id)) {
            return true;
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated Use event system instead. This method will be removed in v6.0
     */
    public function inflector(string $type, ?callable $callback = null): InflectorInterface
    {
        trigger_error(
            'Inflectors are deprecated. Use the event system with ServiceResolvedEvent instead.',
            E_USER_DEPRECATED
        );

        return $this->inflectors->add($type, $callback);
    }

    public function afterResolve(string $type, callable $callback): EventFilter
    {
        return $this->listen(ServiceResolvedEvent::class, function (ServiceResolvedEvent $event) use ($callback) {
            $callback($event->getResolved());
        })->forType($type);
    }

    public function delegate(ContainerInterface $container): self
    {
        $this->delegates[] = $container;

        if ($container instanceof ContainerAwareInterface) {
            $container->setContainer($this);
        }

        return $this;
    }

    public function getDelegate(string $class): ContainerInterface
    {
        foreach ($this->delegates as $delegate) {
            if ($delegate instanceof $class) {
                return $delegate;
            }
        }

        throw new NotFoundException(sprintf(
            'No delegate container of type "%s" is configured',
            $class
        ));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolve(string $id, bool $new = false): mixed
    {
        if ($this->eventDispatcher->hasListenersFor(BeforeResolveEvent::class)) {
            $beforeEvent = new BeforeResolveEvent($id, $new);
            $this->dispatchEvent($beforeEvent);

            if ($beforeEvent->hasResolution()) {
                return $beforeEvent->getResolved();
            }
        }

        if ($this->definitions->has($id)) {
            $definition = $this->definitions->getDefinition($id);
            $definitionTags = $this->getDefinitionTags($definition);

            if ($this->eventDispatcher->hasListenersFor(DefinitionResolvedEvent::class)) {
                $definitionEvent = new DefinitionResolvedEvent($id, $definition, $definitionTags, $new);
                $this->dispatchEvent($definitionEvent);

                if ($definitionEvent->hasResolution()) {
                    $resolved = $definitionEvent->getResolved();
                } else {
                    $resolved = $new ? $this->definitions->resolveNew($id) : $this->definitions->resolve($id);
                }
            } else {
                $resolved = $new ? $this->definitions->resolveNew($id) : $this->definitions->resolve($id);
            }

            $resolved = $this->inflectors->inflect($resolved);

            if ($this->eventDispatcher->hasListenersFor(ServiceResolvedEvent::class)) {
                $objectEvent = new ServiceResolvedEvent($id, $resolved, $definition, $definitionTags, $new);
                $this->dispatchEvent($objectEvent);
                return $objectEvent->getResolved();
            }

            return $resolved;
        }

        if ($this->definitions->hasTag($id)) {
            $arrayOf = $new
                ? $this->definitions->resolveTaggedNew($id)
                : $this->definitions->resolveTagged($id);

            $hasServiceListeners = $this->eventDispatcher->hasListenersFor(ServiceResolvedEvent::class);

            array_walk($arrayOf, function (&$resolved) use ($id, $new, $hasServiceListeners) {
                $resolved = $this->inflectors->inflect($resolved);

                if ($hasServiceListeners) {
                    $objectEvent = new ServiceResolvedEvent($id, $resolved, null, [$id], $new);
                    $this->dispatchEvent($objectEvent);
                    $resolved = $objectEvent->getResolved();
                }
            });

            return $arrayOf;
        }

        if ($this->providers->provides($id)) {
            $this->providers->register($id);

            if (
                false === $this->definitions->has($id) && // @phpstan-ignore-line
                false === $this->definitions->hasTag($id) // @phpstan-ignore-line
            ) {
                throw new ContainerException(sprintf(
                    'Service provider lied about providing (%s) service',
                    $id
                ));
            }

            return $this->resolve($id, $new); // @phpstan-ignore-line
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                $resolved = $delegate->get($id);
                $resolved = $this->inflectors->inflect($resolved);

                if ($this->eventDispatcher->hasListenersFor(ServiceResolvedEvent::class)) {
                    $objectEvent = new ServiceResolvedEvent($id, $resolved, null, [], $new);
                    $this->dispatchEvent($objectEvent);
                    return $objectEvent->getResolved();
                }

                return $resolved;
            }
        }

        throw new NotFoundException(sprintf('Alias (%s) is not being managed by the container or delegates', $id));
    }

    protected function getDefinitionTags(DefinitionInterface $definition): array
    {
        return $definition->getTags();
    }
}
