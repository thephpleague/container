<?php

declare(strict_types=1);

namespace League\Container\Compiler;

use InvalidArgumentException;

final readonly class CompilationConfig
{
    private const string IDENTIFIER_PATTERN = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';
    private const string NAMESPACE_PATTERN = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*$/';

    public function __construct(
        public string $namespace = '',
        public string $className = 'CompiledContainer',
    ) {
        if ($this->className === '' || !preg_match(self::IDENTIFIER_PATTERN, $this->className)) {
            throw new InvalidArgumentException(
                sprintf('Class name "%s" is not a valid PHP identifier.', $this->className),
            );
        }

        if ($this->namespace !== '' && !preg_match(self::NAMESPACE_PATTERN, $this->namespace)) {
            throw new InvalidArgumentException(
                sprintf('Namespace "%s" is not a valid PHP namespace.', $this->namespace),
            );
        }
    }
}
