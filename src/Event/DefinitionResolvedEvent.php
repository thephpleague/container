<?php

declare(strict_types=1);

namespace League\Container\Event;

use League\Container\Definition\DefinitionInterface;

final class DefinitionResolvedEvent extends ContainerEvent
{
    public function __construct(
        string $id,
        DefinitionInterface $definition,
        array $tags = [],
        protected bool $new = false,
    ) {
        parent::__construct($id, $definition, $tags);
    }

    public function getDefinition(): DefinitionInterface
    {
        return $this->definition;
    }

    public function setDefinition(DefinitionInterface $definition): void
    {
        $this->definition = $definition;
    }

    public function isNew(): bool
    {
        return $this->new;
    }
}
