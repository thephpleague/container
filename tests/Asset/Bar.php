<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class Bar implements BarInterface
{
    protected mixed $something;

    public function setSomething(mixed $something): void
    {
        $this->something = $something;
    }

    public function getSomething(): mixed
    {
        return $this->something;
    }
}
