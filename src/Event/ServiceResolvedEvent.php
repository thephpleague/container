<?php

declare(strict_types=1);

namespace League\Container\Event;

use League\Container\Definition\DefinitionInterface;

final class ServiceResolvedEvent extends ContainerEvent
{
    public function __construct(
        string $id,
        mixed $resolved,
        ?DefinitionInterface $definition = null,
        array $tags = [],
        protected bool $new = false,
    ) {
        parent::__construct($id, $definition, $tags, $resolved);
        $this->resolutionProvided = true;
    }

    public function isNew(): bool
    {
        return $this->new;
    }

    public function isInstanceOf(string $type): bool
    {
        return is_object($this->resolved) && $this->resolved instanceof $type;
    }

    public function getServiceType(): string
    {
        return is_object($this->resolved) ? get_class($this->resolved) : gettype($this->resolved);
    }
}
