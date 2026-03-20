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
use League\Container\ServiceProvider\ServiceProviderAggregate;
use League\Container\ServiceProvider\ServiceProviderAggregateInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements DefinitionContainerInterface
{
    use EventAwareTrait;

    /** @var list<ContainerInterface> */
    protected array $delegates = [];

    /** @var list<string> */
    protected array $resolutionStack = [];

    public function __construct(
        protected DefinitionAggregateInterface $definitions = new DefinitionAggregate(),
        protected ServiceProviderAggregateInterface $providers = new ServiceProviderAggregate(),
        protected bool $defaultToShared = false,
        protected bool $defaultToOverwrite = false,
    ) {
        $this->definitions->setContainer($this);
        $this->providers->setContainer($this);

        $this->eventDispatcher = new EventDispatcher();
    }

    #[Override]
    public function add(string $id, mixed $concrete = null, bool $overwrite = false): DefinitionInterface
    {
        $toOverwrite = $this->defaultToOverwrite || $overwrite;
        $concrete ??= $id;

        if (true === $this->defaultToShared) {
            return $this->addShared($id, $concrete, $toOverwrite);
        }

        $definition = $this->definitions->add($id, $concrete, $toOverwrite);

        if ($this->eventDispatcher?->hasListenersFor(OnDefineEvent::class)) {
            $tags = $this->getDefinitionTags($definition);
            $event = new OnDefineEvent($id, $definition, $tags);
            $this->dispatchEvent($event);
            return $event->getDefinition();
        }

        return $definition;
    }

    #[Override]
    public function addShared(string $id, mixed $concrete = null, bool $overwrite = false): DefinitionInterface
    {
        $toOverwrite = $this->defaultToOverwrite || $overwrite;
        $concrete ??= $id;
        $definition = $this->definitions->addShared($id, $concrete, $toOverwrite);
        $definition->addTag('shared');

        if ($this->eventDispatcher?->hasListenersFor(OnDefineEvent::class)) {
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

    #[Override]
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
            $id,
        ));
    }

    #[Override]
    public function addServiceProvider(ServiceProviderInterface $provider): DefinitionContainerInterface
    {
        $this->providers->add($provider);
        return $this;
    }

    #[Override]
    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Override]
    public function getNew(string $id): mixed
    {
        return $this->resolve($id, true);
    }

    #[Override]
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

    /** @return list<string> */
    public function getDefinitionIds(): array
    {
        return $this->definitions->getAliases();
    }

    /** @return list<string> */
    public function getServiceProviderIds(): array
    {
        return $this->providers->getProvidedIds();
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
            $class,
        ));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolve(string $id, bool $new = false): mixed
    {
        if (in_array($id, $this->resolutionStack, true)) {
            $chain = implode(' -> ', [...$this->resolutionStack, $id]);
            throw new ContainerException(
                sprintf('Circular dependency detected: %s', $chain),
            );
        }

        $stackIndex = count($this->resolutionStack);
        $this->resolutionStack[] = $id;

        try {
            if ($this->eventDispatcher?->hasListenersFor(BeforeResolveEvent::class)) {
                $beforeEvent = new BeforeResolveEvent($id, $new);
                $this->dispatchEvent($beforeEvent);

                if ($beforeEvent->hasResolution()) {
                    return $beforeEvent->getResolved();
                }
            }

            if ($this->definitions->has($id)) {
                $definition = $this->definitions->getDefinition($id);
                $definitionTags = $this->getDefinitionTags($definition);

                if ($this->eventDispatcher?->hasListenersFor(DefinitionResolvedEvent::class)) {
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

                if ($this->eventDispatcher?->hasListenersFor(ServiceResolvedEvent::class)) {
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

                $hasServiceListeners = $this->eventDispatcher?->hasListenersFor(ServiceResolvedEvent::class) ?? false;

                array_walk($arrayOf, function (&$resolved) use ($id, $new, $hasServiceListeners) {
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
                    false === $this->definitions->has($id) // @phpstan-ignore identical.alwaysTrue, booleanAnd.alwaysTrue
                    && false === $this->definitions->hasTag($id) // @phpstan-ignore identical.alwaysTrue
                ) {
                    throw new ContainerException(sprintf(
                        'Service provider (%s) claimed to provide (%s) but failed to register it',
                        $this->providers->providerClassFor($id),
                        $id,
                    ));
                }

                array_splice($this->resolutionStack, $stackIndex, 1); // @phpstan-ignore deadCode.unreachable
                return $this->resolve($id, $new);
            }

            foreach ($this->delegates as $delegate) {
                if ($delegate->has($id)) {
                    $resolved = $delegate->get($id);

                    if ($this->eventDispatcher?->hasListenersFor(ServiceResolvedEvent::class)) {
                        $objectEvent = new ServiceResolvedEvent($id, $resolved, null, [], $new);
                        $this->dispatchEvent($objectEvent);
                        return $objectEvent->getResolved();
                    }

                    return $resolved;
                }
            }

            throw NotFoundException::forAlias($id, $this->definitions->getAliases(), $this->resolutionStack);
        } finally {
            if (isset($this->resolutionStack[$stackIndex]) && $this->resolutionStack[$stackIndex] === $id) {
                array_splice($this->resolutionStack, $stackIndex, 1);
            }
        }
    }

    /** @return list<string> */
    protected function getDefinitionTags(DefinitionInterface $definition): array
    {
        return $definition->getTags();
    }
}
