<?php declare(strict_types=1);

namespace League\Container\ServiceProvider;

use League\Container\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $provides = [];

    /**
     * @var string
     */
    protected $signature;

    /**
     * {@inheritdoc}
     */
    public function provides(string $alias): bool
    {
        return (in_array($alias, $this->provides));
    }

    /**
     * {@inheritdoc}
     */
    public function withSignature(string $signature): ServiceProviderInterface
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSignature(): string
    {
        return (is_null($this->signature)) ? get_class($this) : $this->signature;
    }
}
