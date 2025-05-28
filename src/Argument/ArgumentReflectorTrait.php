<?php

declare(strict_types=1);

namespace League\Container\Argument;

use League\Container\Attribute\AttributeInterface;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Container\{ContainerAwareInterface, DefinitionContainerInterface};
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use ReflectionAttribute;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

trait ArgumentReflectorTrait
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []): array
    {
        $params = $method->getParameters();
        $arguments = [];

        foreach ($params as $param) {
            $name = $param->getName();

            // if we've been given a value for the argument, treat as literal
            if (array_key_exists($name, $args)) {
                $arguments[] = new LiteralArgument($args[$name]);
                continue;
            }

            // next we see if we have an attribute that can resolve the argument (if enabled)
            if ($this->getMode() & ReflectionContainer::ATTRIBUTE_RESOLUTION) {
                $attrs = $param->getAttributes();

                foreach ($attrs as $attr) {
                    if ($argument = $this->resolveArgumentFromAttribute($attr)) {
                        $arguments[] = $argument;
                        continue 2;
                    }
                }
            }

            $type = $param->getType();

            // if we have a union type, loop until we can resolve
            if ($type instanceof ReflectionUnionType) {
                $this->throwParameterException(
                    $name,
                    'union',
                    $param->getDeclaringClass()?->getName(),
                    $method->getName(),
                    $method instanceof ReflectionMethod ? $method->isClosure() : false,
                    'Union types are not supported'
                );
            }

            // then we check if we have a type hint (if auto wiring is enabled)
            if ($this->getMode() & ReflectionContainer::AUTO_WIRING && $type instanceof ReflectionNamedType) {
                $arguments[] = $this->resolveArgumentForNamedType($param, $type);
                continue;
            }

            // finally we check if we have a default value
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = new LiteralArgument($param->getDefaultValue());
                continue;
            }

            $this->throwParameterException(
                $name,
                $type instanceof ReflectionNamedType ? $type->getName() : 'unknown',
                $param->getDeclaringClass()?->getName(),
                $method->getName(),
                $method instanceof ReflectionMethod ? $method->isClosure() : false,
                'No default value available and no type hint to resolve'
            );
        }

        return $this->resolveArguments($arguments);
    }

    protected function resolveArgumentFromAttribute(ReflectionAttribute $attribute): LiteralArgumentInterface|false
    {
        $attrClass = $attribute->getName();

        if (is_subclass_of($attrClass, AttributeInterface::class)) {
            $attrClass = $attribute->newInstance();

            if ($attrClass instanceof ContainerAwareInterface) {
                $attrClass->setContainer($this->getContainer());
            }

            // purposely don't define a type here so that any typing errors
            // from the consuming code bubble up
            /** @var AttributeInterface $attrClass */
            return new LiteralArgument($attrClass->resolve(), null);
        }

        return false;
    }

    protected function resolveArgumentForNamedType(
        ReflectionParameter $param,
        ReflectionNamedType $type,
    ): ResolvableArgumentInterface {
        $typeHint = $type->getName();

        if ($type->getName() === 'mixed') {
            $this->throwParameterException(
                $param->getName(),
                'mixed',
                $param->getDeclaringClass()?->getName(),
                $param->getDeclaringFunction()->getName(),
                $param->getDeclaringFunction()->isClosure(),
                'Mixed types are not supported'
            );
        }

        if ($param->isDefaultValueAvailable()) {
            return new DefaultValueArgument($typeHint, $param->getDefaultValue());
        }

        return new ResolvableArgument($typeHint);
    }

    public function throwParameterException(
        string $name,
        string $type,
        ?string $declaringClass = null,
        ?string $declaringFunction = null,
        bool $isClosure = false,
        ?string $additionalMessage = null
    ): void {
        throw new NotFoundException(sprintf(
            'Unable to resolve parameter ($%s) with type (%s) in %s%s%s()%s',
            $name,
            $type,
            $declaringClass ? $declaringClass . '::' : '',
            $declaringFunction ?? '',
            $isClosure ? ' [closure]' : '',
            $additionalMessage ? ' - ' . $additionalMessage : ''
        ));
    }

    abstract public function getContainer(): DefinitionContainerInterface;
    abstract public function getMode(): int;
    abstract public function resolveArguments(array $arguments): array;
}
