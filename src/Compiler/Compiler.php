<?php

declare(strict_types=1);

namespace League\Container\Compiler;

use League\Container\Container;

final readonly class Compiler
{
    public function __construct(
        private DefinitionAnalyser $analyser = new DefinitionAnalyser(),
        private CodeGenerator $generator = new CodeGenerator(),
    ) {}

    public function compile(Container|string $container, CompilationConfig $config): CompilationResult
    {
        $container = $this->resolveContainer($container);
        $analysisResult = $this->analyser->analyse($container);

        if ($analysisResult->hasErrors()) {
            throw new CompilationException($analysisResult->errors);
        }

        $sourceHash = $this->computeHash($analysisResult);

        $phpSource = $this->generator->generate(
            $analysisResult->compiledDefinitions,
            $analysisResult->dependencyGraph,
            $config,
            $sourceHash,
        );

        $fullyQualifiedClassName = $config->namespace !== ''
            ? $config->namespace . '\\' . $config->className
            : $config->className;

        return new CompilationResult(
            phpSource: $phpSource,
            fullyQualifiedClassName: $fullyQualifiedClassName,
            sourceHash: $sourceHash,
            serviceCount: count($analysisResult->compiledDefinitions),
            warnings: $analysisResult->warnings,
        );
    }

    public function isStale(string $compiledClass, Container|string $container): bool
    {
        if (!class_exists($compiledClass) || !defined($compiledClass . '::SOURCE_HASH')) {
            throw new CompilationException([], sprintf(
                'Compiled class "%s" does not exist or does not define a SOURCE_HASH constant.',
                $compiledClass,
            ));
        }

        $container = $this->resolveContainer($container);
        $analysisResult = $this->analyser->analyse($container);
        $currentHash = $this->computeHash($analysisResult);

        return $currentHash !== $compiledClass::SOURCE_HASH;
    }

    private function resolveContainer(Container|string $container): Container
    {
        if ($container instanceof Container) {
            return $container;
        }

        if (!file_exists($container)) {
            throw new CompilationException([], sprintf(
                'Bootstrap file "%s" does not exist.',
                $container,
            ));
        }

        $resolved = require $container;

        if (!$resolved instanceof Container) {
            throw new CompilationException([], sprintf(
                'Bootstrap file "%s" must return an instance of %s, got %s.',
                $container,
                Container::class,
                get_debug_type($resolved),
            ));
        }

        return $resolved;
    }

    private function computeHash(AnalysisResult $analysisResult): string
    {
        $definitionIds = array_map(
            static fn(CompiledDefinition $definition): string => $definition->id,
            $analysisResult->compiledDefinitions,
        );

        $canonical = [];

        foreach ($analysisResult->compiledDefinitions as $definition) {
            $sortedTags = $definition->tags;
            sort($sortedTags);

            $sortedMethodCalls = $definition->methodCalls;
            usort($sortedMethodCalls, static fn(array $a, array $b): int => strcmp($a['method'], $b['method']));

            $canonical[] = implode('|', [
                $definition->id,
                $definition->concreteType->value,
                $definition->shared ? '1' : '0',
                $definition->concreteClass ?? '',
                $definition->factoryClass ?? '',
                $definition->factoryMethod ?? '',
                implode(',', $definition->resolvedArguments),
                implode(',', array_map(
                    static fn(array $call): string => $call['method'] . ':' . implode(';', $call['arguments']),
                    $sortedMethodCalls,
                )),
                implode(',', $sortedTags),
            ]);
        }

        sort($canonical);

        $graphEdges = [];
        foreach ($definitionIds as $id) {
            $dependencies = $analysisResult->dependencyGraph->getDependencies($id);
            sort($dependencies);
            foreach ($dependencies as $dependency) {
                $graphEdges[] = $id . '->' . $dependency;
            }
        }

        sort($graphEdges);

        $payload = implode("\n", [
            CodeGenerator::COMPILER_VERSION,
            implode("\n", $canonical),
            implode("\n", $graphEdges),
        ]);

        return hash('sha256', $payload);
    }
}
