<?php

declare(strict_types=1);

namespace League\Container\Compiler;

use Closure;
use League\Container\Argument\DefaultValueInterface;
use League\Container\Argument\Literal\CallableArgument;
use League\Container\Argument\Literal\ObjectArgument;
use League\Container\Argument\LiteralArgumentInterface;
use League\Container\Argument\ResolvableArgumentInterface;

final readonly class ArgumentCompiler
{
    /**
     * @param list<string> $knownServices
     */
    public function compile(
        mixed $argument,
        array $knownServices,
        ?string $serviceId = null,
        ?int $argumentPosition = null,
    ): string {
        if ($argument instanceof ObjectArgument) {
            return $this->rejectObjectArgument($argument, $serviceId, $argumentPosition);
        }

        if ($argument instanceof CallableArgument) {
            return $this->compileCallableArgument($argument, $serviceId, $argumentPosition);
        }

        if ($argument instanceof DefaultValueInterface) {
            return $this->compileDefaultValueArgument($argument, $knownServices);
        }

        if ($argument instanceof ResolvableArgumentInterface) {
            return sprintf('$this->get(%s)', $this->exportString($argument->getValue()));
        }

        if ($argument instanceof LiteralArgumentInterface) {
            return $this->compileLiteralArgument($argument);
        }

        if (is_string($argument)) {
            return in_array($argument, $knownServices, strict: true)
                ? sprintf('$this->get(%s)', $this->exportString($argument))
                : $this->exportString($argument);
        }

        if (is_null($argument)) {
            return 'null';
        }

        return var_export($argument, true);
    }

    private function compileLiteralArgument(LiteralArgumentInterface $argument): string
    {
        $value = $argument->getValue();

        if (is_string($value)) {
            return $this->exportString($value);
        }

        if (is_null($value)) {
            return 'null';
        }

        return var_export($value, true);
    }

    /** @param list<string> $knownServices */
    private function compileDefaultValueArgument(DefaultValueInterface $argument, array $knownServices): string
    {
        $serviceId = $argument->getValue();

        if (in_array($serviceId, $knownServices, strict: true)) {
            return sprintf('$this->get(%s)', $this->exportString($serviceId));
        }

        $defaultValue = $argument->getDefaultValue();

        if (is_null($defaultValue)) {
            return 'null';
        }

        return var_export($defaultValue, true);
    }

    private function compileCallableArgument(
        CallableArgument $argument,
        ?string $serviceId,
        ?int $argumentPosition,
    ): string {
        $callable = $argument->getValue();

        if ($callable instanceof Closure) {
            throw new CompilationException(
                errors: [
                    [
                        'serviceId' => $serviceId ?? 'unknown',
                        'errorType' => 'closure_argument',
                        'message' => $this->closureArgumentMessage($argumentPosition),
                        'suggestedFix' => 'Replace the closure argument with a named callable array such as [MyClass::class, \'method\'], or extract the logic into a dedicated service and inject that instead.',
                    ],
                ],
            );
        }

        if (is_array($callable) && count($callable) === 2 && is_string($callable[0]) && is_string($callable[1])) {
            return sprintf('[\\%s::class, %s]', ltrim($callable[0], '\\'), $this->exportString($callable[1]));
        }

        if (is_string($callable)) {
            return $this->exportString($callable);
        }

        throw new CompilationException(
            errors: [
                [
                    'serviceId' => $serviceId ?? 'unknown',
                    'errorType' => 'unsupported_callable',
                    'message' => 'The callable argument type is not supported for compilation.',
                    'suggestedFix' => 'Use a named callable array such as [MyClass::class, \'method\'] or a plain string function name.',
                ],
            ],
        );
    }

    private function rejectObjectArgument(
        ObjectArgument $argument,
        ?string $serviceId,
        ?int $argumentPosition,
    ): never {
        $objectClass = get_class($argument->getValue());

        throw new CompilationException(
            errors: [
                [
                    'serviceId' => $serviceId ?? 'unknown',
                    'errorType' => 'object_argument',
                    'message' => sprintf(
                        'Argument%s is an object instance of "%s" which cannot be serialised into compiled code.',
                        $argumentPosition !== null ? sprintf(' at position %d', $argumentPosition) : '',
                        $objectClass,
                    ),
                    'suggestedFix' => sprintf(
                        'Register "%s" as a service in the container and inject it as a resolvable dependency instead.',
                        $objectClass,
                    ),
                ],
            ],
        );
    }

    private function closureArgumentMessage(?int $argumentPosition): string
    {
        return sprintf(
            'Argument%s is a Closure which cannot be serialised into compiled code.',
            $argumentPosition !== null ? sprintf(' at position %d', $argumentPosition) : '',
        );
    }

    private function exportString(string $value): string
    {
        return sprintf("'%s'", addcslashes($value, "'\\"));
    }
}
