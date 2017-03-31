<?php declare(strict_types=1);

namespace League\Container\Inflector;

use Generator;
use League\Container\ContainerAwareTrait;

class InflectorAggregate implements InflectorAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var \League\Container\Inflector[]
     */
    protected $inflectors = [];

    /**
     * {@inheritdoc}
     */
    public function add(string $type, callable $callback = null): Inflector
    {
        $inflector          = new Inflector($type, $callback);
        $this->inflectors[] = $inflector;

        return $inflector;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Generator
    {
        $count = count($this->inflectors);

        for ($i = 0; $i < $count; $i++) {
            yield $this->inflectors[$i];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function inflect($object)
    {
        foreach ($this->getIterator() as $inflector) {
            if (! is_subclass_of($object, $inflector->getType())) {
                continue;
            }

            $inflector->setContainer($this->getContainer());
            $inflector->inflect($object);
        }

        return $object;
    }
}