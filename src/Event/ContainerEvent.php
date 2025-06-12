<?php

declare(strict_types=1);

namespace League\Container\Event;

use League\Container\Definition\DefinitionInterface;
use Psr\EventDispatcher\StoppableEventInterface;

abstract class ContainerEvent implements StoppableEventInterface
{
    protected bool $propagationStopped = false;

    public function __construct(
        protected string $id,
        protected ?DefinitionInterface $definition = null,
        protected array $tags = [],
        protected mixed $resolved = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDefinition(): ?DefinitionInterface
    {
        return $this->definition;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    public function getResolved(): mixed
    {
        return $this->resolved;
    }

    public function setResolved(mixed $resolved): void
    {
        $this->resolved = $resolved;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
