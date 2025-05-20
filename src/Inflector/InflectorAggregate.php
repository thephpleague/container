<?php

declare(strict_types=1);

namespace League\Container\Inflector;

use Generator;
use League\Container\ContainerAwareTrait;

class InflectorAggregate implements InflectorAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var Inflector[]
     */
    protected array $inflectors = [];

    public function add(string $type, ?callable $callback = null): Inflector
    {
        $inflector = new Inflector($type, callback: $callback);
        $this->inflectors[] = $inflector;
        return $inflector;
    }

    public function inflect(mixed $object): mixed
    {
        foreach ($this as $inflector) {
            $type = $inflector->getType();

            if ($object instanceof $type) {
                $inflector->setContainer($this->getContainer());
                $inflector->inflect($object);
            }
        }

        return $object;
    }

    public function getIterator(): Generator
    {
        yield from $this->inflectors;
    }
}
