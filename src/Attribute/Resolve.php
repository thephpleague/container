<?php

declare(strict_types=1);

namespace League\Container\Attribute;

use Attribute;
use League\Container\{ContainerAwareInterface, ContainerAwareTrait, Exception\NotFoundException};
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class Resolve implements AttributeInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(protected string $resolver, protected string $path)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(): mixed
    {
        $resolved = $this->getContainer()->get($this->resolver);

        foreach (explode('.', $this->path) as $segment) {
            $resolved = $this->getResolvedValue($resolved, $segment);
        }

        return $resolved;
    }

    protected function getResolvedValue(mixed $previous, string $next): mixed
    {
        if (is_object($previous) && method_exists($previous, $next)) {
            return $previous->{$next}();
        }

        if (is_object($previous) && property_exists($previous, $next)) {
            return $previous->{$next};
        }

        if (is_array($previous) && array_key_exists($next, $previous)) {
            return $previous[$next];
        }

        throw new NotFoundException(
            sprintf(
                'Unable to resolve value for path (%s) on resolver (%s)',
                $this->path,
                $this->resolver
            )
        );
    }
}
