<?php

declare(strict_types=1);

namespace League\Container\Argument;

use InvalidArgumentException;

class LiteralArgument implements LiteralArgumentInterface
{
    public const TYPE_ARRAY = 'array';
    public const TYPE_BOOL = 'boolean';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_CALLABLE = 'callable';
    public const TYPE_DOUBLE = 'double';
    public const TYPE_FLOAT = 'double';
    public const TYPE_INT = 'integer';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_OBJECT = 'object';
    public const TYPE_STRING = 'string';

    protected mixed $value;

    public function __construct(mixed $value, string $type = null)
    {
        if (
            null === $type
            || ($type === self::TYPE_CALLABLE && is_callable($value))
            || ($type === self::TYPE_OBJECT && is_object($value))
            || gettype($value) === $type
        ) {
            $this->value = $value;
        } else {
            throw new InvalidArgumentException('Incorrect type for value.');
        }
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
