<?php

declare(strict_types=1);

namespace League\Container\Attribute;

use Attribute;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class Inject implements AttributeInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(protected string $id) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Override]
    public function resolve(): mixed
    {
        return $this->getContainer()->get($this->id);
    }
}
