<?php

declare(strict_types=1);

namespace League\Container\Argument;

use InvalidArgumentException;
use Override;

class LiteralArgument implements LiteralArgumentInterface
{
    public const string TYPE_ARRAY = 'array';
    public const string TYPE_BOOLEAN = 'boolean';
    public const string TYPE_BOOL = self::TYPE_BOOLEAN;
    public const string TYPE_CALLABLE = 'callable';
    public const string TYPE_DOUBLE = 'double';
    public const string TYPE_FLOAT = self::TYPE_DOUBLE;
    public const string TYPE_INTEGER = 'integer';
    public const string TYPE_INT = self::TYPE_INTEGER;
    public const string TYPE_OBJECT = 'object';
    public const string TYPE_STRING = 'string';

    protected mixed $value;

    public function __construct(mixed $value, ?string $type = null)
    {
        if (
            null === $type
            || ($type === self::TYPE_CALLABLE && is_callable($value))
            || ($type === self::TYPE_OBJECT && is_object($value))
            || gettype($value) === $type
        ) {
            $this->value = $value;
        } else {
            throw new InvalidArgumentException(
                sprintf('Expected literal argument type "%s", got "%s"', $type, get_debug_type($value)),
            );
        }
    }

    #[Override]
    public function getValue(): mixed
    {
        return $this->value;
    }
}
