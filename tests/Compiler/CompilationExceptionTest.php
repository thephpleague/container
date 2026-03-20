<?php

declare(strict_types=1);

namespace League\Container\Test\Compiler;

use League\Container\Compiler\CompilationException;
use League\Container\Exception\ContainerException;
use PHPUnit\Framework\TestCase;

class CompilationExceptionTest extends TestCase
{
    public function testExtendsContainerException(): void
    {
        $exception = new CompilationException([]);

        $this->assertInstanceOf(ContainerException::class, $exception);
    }

    public function testGetErrorsReturnsAllProvidedErrors(): void
    {
        $errors = [
            [
                'serviceId' => 'App\Service',
                'errorType' => 'closure',
                'message' => 'Cannot compile a closure.',
                'suggestedFix' => 'Replace the closure with a factory class.',
            ],
            [
                'serviceId' => 'App\Broken',
                'errorType' => 'missing_class',
                'message' => 'Class "App\Broken" does not exist.',
                'suggestedFix' => 'Ensure the class is autoloadable.',
            ],
        ];

        $exception = new CompilationException($errors);

        $this->assertSame($errors, $exception->getErrors());
    }

    public function testMessageIsBuiltFromErrorsWhenNoMessageProvided(): void
    {
        $errors = [
            [
                'serviceId' => 'App\Service',
                'errorType' => 'closure',
                'message' => 'Cannot compile a closure.',
                'suggestedFix' => 'Use a factory.',
            ],
        ];

        $exception = new CompilationException($errors);

        $this->assertStringContainsString('closure', $exception->getMessage());
        $this->assertStringContainsString('App\Service', $exception->getMessage());
        $this->assertStringContainsString('Cannot compile a closure.', $exception->getMessage());
    }

    public function testCustomMessageOverridesGeneratedMessage(): void
    {
        $exception = new CompilationException([], 'Custom failure message.');

        $this->assertSame('Custom failure message.', $exception->getMessage());
    }

    public function testEmptyErrorsProducesDefaultMessage(): void
    {
        $exception = new CompilationException([]);

        $this->assertSame('Container compilation failed.', $exception->getMessage());
    }

    public function testMultipleErrorsAreEachRepresentedInMessage(): void
    {
        $errors = [
            [
                'serviceId' => 'App\First',
                'errorType' => 'closure',
                'message' => 'First error.',
                'suggestedFix' => '',
            ],
            [
                'serviceId' => 'App\Second',
                'errorType' => 'missing_class',
                'message' => 'Second error.',
                'suggestedFix' => '',
            ],
        ];

        $exception = new CompilationException($errors);

        $this->assertStringContainsString('App\First', $exception->getMessage());
        $this->assertStringContainsString('App\Second', $exception->getMessage());
    }
}
