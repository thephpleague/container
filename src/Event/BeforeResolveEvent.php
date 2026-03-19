<?php

declare(strict_types=1);

namespace League\Container\Event;

final class BeforeResolveEvent extends ContainerEvent
{
    public function __construct(string $id, protected bool $new = false)
    {
        parent::__construct($id);
    }

    public function isNew(): bool
    {
        return $this->new;
    }

    public function setNew(bool $new): void
    {
        $this->new = $new;
    }
}
