<?php

declare(strict_types=1);

namespace League\Container\Compiler;

use DateTimeImmutable;
use DateTimeInterface;

final readonly class CodeGenerator
{
    public const string COMPILER_VERSION = '1.0.0';

    /** @param list<CompiledDefinition> $compiledDefinitions */
    public function generate(
        array $compiledDefinitions,
        DependencyGraph $graph,
        CompilationConfig $config,
        string $sourceHash,
    ): string {
        $methodNames = $this->buildMethodNameMap($compiledDefinitions);
        $tagMap = $this->buildTagMap($compiledDefinitions);
        $tagMethodNames = $this->buildTagMethodNames(array_keys($tagMap), array_keys($methodNames));

        return implode("\n", array_filter([
            $this->renderFileHeader(),
            $config->namespace !== '' ? $this->renderNamespace($config->namespace) : null,
            $this->renderImports(),
            $this->renderClassOpen($config->className, $compiledDefinitions, $sourceHash),
            $this->renderSharedProperty(),
            $this->renderGetMethod($compiledDefinitions, $methodNames, $tagMap, $tagMethodNames),
            $this->renderHasMethod($compiledDefinitions, $tagMap),
            $this->renderFactoryMethods($compiledDefinitions, $methodNames),
            $this->renderTagFactoryMethods($tagMap, $tagMethodNames),
            $this->renderClassClose(),
        ], static fn(mixed $v): bool => $v !== null));
    }

    private function renderFileHeader(): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n";
    }

    private function renderNamespace(string $namespace): string
    {
        return "namespace {$namespace};\n";
    }

    private function renderImports(): string
    {
        return "use Psr\\Container\\ContainerInterface;\n";
    }

    /** @param list<CompiledDefinition> $compiledDefinitions */
    private function renderClassOpen(
        string $className,
        array $compiledDefinitions,
        string $sourceHash,
    ): string {
        $compiledAt = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
        $serviceCount = count($compiledDefinitions);

        return implode("\n", [
            "final class {$className} implements ContainerInterface",
            '{',
            "    public const string COMPILED_AT = '{$compiledAt}';",
            "    public const string COMPILER_VERSION = '" . self::COMPILER_VERSION . "';",
            "    public const string SOURCE_HASH = '" . addcslashes($sourceHash, "'\\") . "';",
            "    public const int SERVICE_COUNT = {$serviceCount};",
        ]);
    }

    private function renderSharedProperty(): string
    {
        return "\n    private array \$shared = [];\n";
    }

    /**
     * @param list<CompiledDefinition> $compiledDefinitions
     * @param array<string, string> $methodNames
     * @param array<string, list<string>> $tagMap
     * @param array<string, string> $tagMethodNames
     */
    private function renderGetMethod(
        array $compiledDefinitions,
        array $methodNames,
        array $tagMap,
        array $tagMethodNames,
    ): string {
        $lines = [
            '    public function get(string $id): mixed',
            '    {',
            '        return match ($id) {',
        ];

        $serviceIds = array_map(static fn(CompiledDefinition $d): string => $d->id, $compiledDefinitions);

        foreach ($compiledDefinitions as $definition) {
            $key = $this->exportId($definition->id);
            $methodName = $methodNames[$definition->id];

            if ($definition->shared) {
                $lines[] = "            {$key} => \$this->shared[{$key}] ??= \$this->{$methodName}(),";
            } else {
                $lines[] = "            {$key} => \$this->{$methodName}(),";
            }
        }

        foreach ($tagMap as $tag => $taggedIds) {
            if (in_array($tag, $serviceIds, strict: true)) {
                continue;
            }

            $tagMethodName = $tagMethodNames[$tag];
            $tagKey = $this->exportId($tag);
            $lines[] = "            {$tagKey} => \$this->{$tagMethodName}(),";
        }

        $lines[] = "            default => throw new \\League\\Container\\Exception\\NotFoundException(sprintf('Service \"%s\" is not compiled in this container.', \$id)),";
        $lines[] = '        };';
        $lines[] = '    }';

        return "\n" . implode("\n", $lines);
    }

    /**
     * @param list<CompiledDefinition> $compiledDefinitions
     * @param array<string, list<string>> $tagMap
     */
    private function renderHasMethod(array $compiledDefinitions, array $tagMap): string
    {
        $lines = [
            '    public function has(string $id): bool',
            '    {',
            '        return match ($id) {',
        ];

        $serviceIds = array_map(static fn(CompiledDefinition $d): string => $d->id, $compiledDefinitions);
        $allKnownIds = $serviceIds;

        foreach (array_keys($tagMap) as $tag) {
            if (!in_array($tag, $serviceIds, strict: true)) {
                $allKnownIds[] = $tag;
            }
        }

        if ($allKnownIds !== []) {
            $keys = implode(', ', array_map(fn(string $id): string => $this->exportId($id), $allKnownIds));
            $lines[] = "            {$keys} => true,";
        }

        $lines[] = '            default => false,';
        $lines[] = '        };';
        $lines[] = '    }';

        return "\n" . implode("\n", $lines);
    }

    /**
     * @param list<CompiledDefinition> $compiledDefinitions
     * @param array<string, string> $methodNames
     */
    private function renderFactoryMethods(array $compiledDefinitions, array $methodNames): string
    {
        $parts = [];

        foreach ($compiledDefinitions as $definition) {
            $methodName = $methodNames[$definition->id];
            $parts[] = $this->renderSingleFactoryMethod($definition, $methodName);
        }

        return implode("\n", $parts);
    }

    private function renderSingleFactoryMethod(CompiledDefinition $definition, string $methodName): string
    {
        $body = $this->renderFactoryBody($definition);

        return "\n    private function {$methodName}(): mixed\n    {\n{$body}\n    }";
    }

    private function renderFactoryBody(CompiledDefinition $definition): string
    {
        if ($definition->methodCalls !== []) {
            return $this->renderFactoryBodyWithMethodCalls($definition);
        }

        return '        return ' . $this->renderInstantiation($definition) . ';';
    }

    private function renderFactoryBodyWithMethodCalls(CompiledDefinition $definition): string
    {
        $lines = [];
        $lines[] = '        $instance = ' . $this->renderInstantiation($definition) . ';';

        foreach ($definition->methodCalls as $call) {
            $args = implode(', ', $call['arguments']);
            $lines[] = "        \$instance->{$call['method']}({$args});";
        }

        $lines[] = '        return $instance;';

        return implode("\n", $lines);
    }

    private function renderInstantiation(CompiledDefinition $definition): string
    {
        $this->validateDefinitionFields($definition);

        return match ($definition->concreteType) {
            ConcreteType::ClassType => $this->renderClassInstantiation($definition),
            ConcreteType::Alias => $this->renderAliasInstantiation($definition),
            ConcreteType::Literal => $this->renderLiteralInstantiation($definition),
            ConcreteType::StaticCallable => $this->renderStaticCallableInstantiation($definition),
            ConcreteType::InstanceCallable => $this->renderInstanceCallableInstantiation($definition),
            ConcreteType::Invokable => $this->renderInvokableInstantiation($definition),
        };
    }

    private function validateDefinitionFields(CompiledDefinition $definition): void
    {
        $requiredFields = match ($definition->concreteType) {
            ConcreteType::Alias => ['concreteClass'],
            ConcreteType::StaticCallable => ['factoryClass', 'factoryMethod'],
            ConcreteType::InstanceCallable => ['factoryClass', 'factoryMethod'],
            ConcreteType::ClassType, ConcreteType::Literal, ConcreteType::Invokable => [],
        };

        $missing = array_filter(
            $requiredFields,
            static fn(string $field): bool => $definition->{$field} === null || $definition->{$field} === '',
        );

        if ($missing !== []) {
            throw new CompilationException(
                errors: [
                    [
                        'serviceId' => $definition->id,
                        'errorType' => 'invalid_definition',
                        'message' => sprintf(
                            'Definition of type "%s" requires non-empty values for: %s.',
                            $definition->concreteType->value,
                            implode(', ', $missing),
                        ),
                        'suggestedFix' => sprintf(
                            'Ensure the definition for "%s" has all required fields populated before compilation.',
                            $definition->id,
                        ),
                    ],
                ],
            );
        }
    }

    private function renderClassInstantiation(CompiledDefinition $definition): string
    {
        $fqcn = '\\' . ltrim($definition->concreteClass ?? $definition->id, '\\');
        $args = implode(', ', $definition->resolvedArguments);

        return "new {$fqcn}({$args})";
    }

    private function renderAliasInstantiation(CompiledDefinition $definition): string
    {
        $fqcn = '\\' . ltrim($definition->concreteClass ?? '', '\\');

        return "\$this->get({$fqcn}::class)";
    }

    private function renderLiteralInstantiation(CompiledDefinition $definition): string
    {
        return $definition->resolvedArguments[0] ?? 'null';
    }

    private function renderStaticCallableInstantiation(CompiledDefinition $definition): string
    {
        $fqcn = '\\' . ltrim($definition->factoryClass ?? '', '\\');
        $method = $definition->factoryMethod ?? '';
        $args = implode(', ', $definition->resolvedArguments);

        return "{$fqcn}::{$method}({$args})";
    }

    private function renderInstanceCallableInstantiation(CompiledDefinition $definition): string
    {
        $fqcn = '\\' . ltrim($definition->factoryClass ?? '', '\\');
        $method = $definition->factoryMethod ?? '';
        $args = implode(', ', $definition->resolvedArguments);

        return "\$this->get({$fqcn}::class)->{$method}({$args})";
    }

    private function renderInvokableInstantiation(CompiledDefinition $definition): string
    {
        $fqcn = '\\' . ltrim($definition->concreteClass ?? $definition->id, '\\');
        $args = implode(', ', $definition->resolvedArguments);

        return "(new {$fqcn}({$args}))()";
    }

    /**
     * @param array<string, list<string>> $tagMap
     * @param array<string, string> $tagMethodNames
     */
    private function renderTagFactoryMethods(array $tagMap, array $tagMethodNames): string
    {
        if ($tagMap === []) {
            return '';
        }

        $parts = [];

        foreach ($tagMap as $tag => $taggedIds) {
            $methodName = $tagMethodNames[$tag];
            $getCalls = array_map(
                fn(string $id): string => '            $this->get(' . $this->exportId($id) . ')',
                $taggedIds,
            );
            $body = implode(",\n", $getCalls);

            $parts[] = "\n    private function {$methodName}(): array\n    {\n        return [\n{$body},\n        ];\n    }";
        }

        return implode("\n", $parts);
    }

    private function renderClassClose(): string
    {
        return "}\n";
    }

    /**
     * @param list<CompiledDefinition> $compiledDefinitions
     * @return array<string, string>
     */
    private function buildMethodNameMap(array $compiledDefinitions): array
    {
        $map = [];
        $seen = [];

        foreach ($compiledDefinitions as $definition) {
            $base = 'create' . $this->sanitiseMethodSuffix($definition->id);
            $name = $base;
            $counter = 2;

            while (in_array($name, $seen, strict: true)) {
                $name = $base . '_' . $counter;
                $counter++;
            }

            $seen[] = $name;
            $map[$definition->id] = $name;
        }

        return $map;
    }

    /**
     * @param list<string> $tags
     * @param list<string> $reservedNames
     * @return array<string, string>
     */
    private function buildTagMethodNames(array $tags, array $reservedNames): array
    {
        $map = [];
        $seen = $reservedNames;

        foreach ($tags as $tag) {
            $base = 'createTag_' . $this->sanitiseMethodSuffix($tag);
            $name = $base;
            $counter = 2;

            while (in_array($name, $seen, strict: true)) {
                $name = $base . '_' . $counter;
                $counter++;
            }

            $seen[] = $name;
            $map[$tag] = $name;
        }

        return $map;
    }

    private function sanitiseMethodSuffix(string $id): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '_', $id) ?? '_';
    }

    /**
     * @param list<CompiledDefinition> $compiledDefinitions
     * @return array<string, list<string>>
     */
    private function buildTagMap(array $compiledDefinitions): array
    {
        $tagMap = [];

        foreach ($compiledDefinitions as $definition) {
            foreach ($definition->tags as $tag) {
                $tagMap[$tag][] = $definition->id;
            }
        }

        return $tagMap;
    }

    private function exportId(string $id): string
    {
        return "'" . addcslashes($id, "'\\") . "'";
    }
}
