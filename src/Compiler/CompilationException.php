<?php

declare(strict_types=1);

namespace League\Container\Compiler;

use League\Container\Exception\ContainerException;
use Throwable;

final class CompilationException extends ContainerException
{
    /**
     * @param list<array{serviceId: string, errorType: string, message: string, suggestedFix: string}> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: $this->buildMessageFromErrors(), $code, $previous);
    }

    /**
     * @return list<array{serviceId: string, errorType: string, message: string, suggestedFix: string}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    private function buildMessageFromErrors(): string
    {
        if ($this->errors === []) {
            return 'Container compilation failed.';
        }

        $lines = array_map(
            static fn(array $error): string => sprintf(
                '[%s] %s: %s',
                $error['errorType'],
                $error['serviceId'],
                $error['message'],
            ),
            $this->errors,
        );

        return implode("\n", $lines);
    }
}
