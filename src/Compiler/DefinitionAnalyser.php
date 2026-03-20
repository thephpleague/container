<?php

declare(strict_types=1);

namespace League\Container\Compiler;

use Closure;
use League\Container\Argument\Literal\ObjectArgument;
use League\Container\Argument\LiteralArgumentInterface;
use League\Container\Attribute\AttributeInterface;
use League\Container\Attribute\Inject;
use League\Container\Attribute\Resolve;
use League\Container\Attribute\Shared;
use League\Container\Container;
use League\Container\Definition\Definition;
use League\Container\Definition\DefinitionInterface;
use League\Container\ReflectionContainer;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;

final readonly class DefinitionAnalyser
{
    public function __construct(
        private ArgumentCompiler $argumentCompiler = new ArgumentCompiler(),
    ) {}

    public function analyse(Container $container): AnalysisResult
    {
        $this->forceRegisterAllProviders($container);

        $definitions = $this->getDefinitions($container);
        $knownServices = $this->buildKnownServicesList($definitions);

        $compiledDefinitions = [];
        $errors = [];
        $warnings = [];
        $graph = new DependencyGraph();
        $tagMap = [];

        $reflectionContainer = $this->findReflectionContainerDelegate($container);
        $autowired = [];

        foreach ($definitions as $definition) {
            $graph->addNode($definition->getAlias());

            $classificationResult = $this->classifyConcrete($definition, $knownServices);

            if ($classificationResult['error'] !== null) {
                $errors[] = $classificationResult['error'];
                continue;
            }

            $concreteType = $classificationResult['concreteType'];
            $concreteClass = $classificationResult['concreteClass'];
            $factoryClass = $classificationResult['factoryClass'];
            $factoryMethod = $classificationResult['factoryMethod'];

            $concrete = $definition->getConcrete();
            $explicitArguments = $definition->getArguments();

            if ($concreteType === ConcreteType::Literal && $concrete instanceof LiteralArgumentInterface && $explicitArguments === []) {
                [$resolvedArguments, $argumentErrors] = $this->compileArguments(
                    [$concrete],
                    $knownServices,
                    $definition->getAlias(),
                );
            } else {
                [$resolvedArguments, $argumentErrors] = $this->compileArguments(
                    $explicitArguments,
                    $knownServices,
                    $definition->getAlias(),
                );
            }

            foreach ($argumentErrors as $error) {
                $errors[] = $error;
            }

            $contextualArguments = $definition->getContextualArguments();

            if ($definition->getArguments() === [] && $concreteClass !== null && $concreteType === ConcreteType::ClassType && ($reflectionContainer !== null || $contextualArguments !== [])) {
                [$resolvedArguments, $autowireErrors, $autowireWarnings] = $this->autowireClass(
                    className: $concreteClass,
                    reflectionContainer: $reflectionContainer,
                    knownServices: $knownServices,
                    graph: $graph,
                    compiledDefinitions: $compiledDefinitions,
                    autowired: $autowired,
                    contextualArguments: $contextualArguments,
                );

                foreach ($autowireErrors as $error) {
                    if (!isset($error['serviceId'])) {
                        $error['serviceId'] = $definition->getAlias();
                    }
                    $errors[] = $error;
                }

                foreach ($autowireWarnings as $warning) {
                    $warnings[] = $warning;
                }
            }

            [$compiledMethodCalls, $methodErrors] = $this->compileMethodCalls(
                $definition->getMethodCalls(),
                $knownServices,
                $definition->getAlias(),
            );

            foreach ($methodErrors as $error) {
                $errors[] = $error;
            }

            $this->registerDependencyEdges($graph, $definition->getAlias(), $resolvedArguments);
            $this->registerDependencyEdges($graph, $definition->getAlias(), $this->flattenMethodArguments($compiledMethodCalls));

            $definitionTags = array_values(array_filter($definition->getTags(), static fn(string $t): bool => $t !== 'shared'));

            foreach ($definitionTags as $tag) {
                $tagMap[$tag][] = $definition->getAlias();
            }

            $compiledDefinitions[] = new CompiledDefinition(
                id: $definition->getAlias(),
                concreteType: $concreteType,
                shared: $definition->isShared(),
                resolvedArguments: $resolvedArguments,
                methodCalls: $compiledMethodCalls,
                tags: $definitionTags,
                concreteClass: $concreteClass,
                factoryClass: $factoryClass,
                factoryMethod: $factoryMethod,
            );
        }

        foreach ($graph->detectCycles() as $cycle) {
            $errors[] = [
                'serviceId' => $cycle[0],
                'errorType' => 'circular_dependency',
                'message' => sprintf('Circular dependency detected: %s', implode(' -> ', $cycle)),
                'suggestedFix' => 'Refactor the dependency chain to remove the cycle. Consider introducing an interface, a factory, or lazy loading to break the circular reference.',
            ];
        }

        $this->detectTagServiceIdCollisions($tagMap, $knownServices, $warnings);
        $this->detectEventListeners($container, $warnings);
        $this->detectNonReflectionDelegates($container, $warnings);

        return new AnalysisResult(
            compiledDefinitions: $compiledDefinitions,
            dependencyGraph: $graph,
            tagMap: $tagMap,
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * @param list<string> $knownServices
     * @param list<CompiledDefinition> $compiledDefinitions
     * @param list<string> $autowired
     * @param array<string, string|object> $contextualArguments
     * @return array{
     *     0: list<string>,
     *     1: list<array{errorType: string, message: string, suggestedFix: string}>,
     *     2: list<string>
     * }
     */
    private function autowireClass(
        string $className,
        ?ReflectionContainer $reflectionContainer,
        array &$knownServices,
        DependencyGraph $graph,
        array &$compiledDefinitions,
        array &$autowired,
        array $contextualArguments = [],
    ): array {
        if (in_array($className, $autowired, strict: true)) {
            return [[], [], []];
        }

        $autowired[] = $className;

        if (!class_exists($className)) {
            return [[], [], []];
        }

        $reflectionClass = new ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return [[], [], []];
        }

        if (!$constructor->isPublic()) {
            return [
                [],
                [
                    [
                        'errorType' => 'non_public_constructor',
                        'message' => sprintf(
                            'Class "%s" has a non-public constructor and cannot be autowired.',
                            $className,
                        ),
                        'suggestedFix' => 'Make the constructor public, or register the service with explicit arguments.',
                    ],
                ],
                [],
            ];
        }

        $params = $constructor->getParameters();

        if ($params === []) {
            return [[], [], []];
        }

        $resolvedArguments = [];
        $errors = [];
        $warnings = [];

        foreach ($params as $param) {
            if ($reflectionContainer !== null && ($reflectionContainer->getMode() & ReflectionContainer::ATTRIBUTE_RESOLUTION)) {
                $attributeResult = $this->resolveParameterFromAttributes(
                    param: $param,
                    knownServices: $knownServices,
                    graph: $graph,
                    compiledDefinitions: $compiledDefinitions,
                    autowired: $autowired,
                    reflectionContainer: $reflectionContainer,
                    ownerClass: $className,
                    errors: $errors,
                    warnings: $warnings,
                );

                if ($attributeResult !== null) {
                    $resolvedArguments[] = $attributeResult;
                    continue;
                }
            }

            $type = $param->getType();

            if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
                $typeLabel = $type instanceof ReflectionUnionType ? 'union' : 'intersection';

                if ($param->isDefaultValueAvailable()) {
                    $resolvedArguments[] = var_export($param->getDefaultValue(), true);
                    continue;
                }

                $errors[] = [
                    'errorType' => $typeLabel . '_type_parameter',
                    'message' => sprintf(
                        'Parameter "$%s" in "%s::__construct()" has a %s type which cannot be autowired.',
                        $param->getName(),
                        $className,
                        $typeLabel,
                    ),
                    'suggestedFix' => 'Use explicit arguments or the #[Inject] attribute to specify which type to inject.',
                ];

                continue;
            }

            $typeHintResolvable = $reflectionContainer !== null
                ? ($reflectionContainer->getMode() & ReflectionContainer::AUTO_WIRING) && $type instanceof ReflectionNamedType
                : $type instanceof ReflectionNamedType && $contextualArguments !== [];

            if ($typeHintResolvable && $type instanceof ReflectionNamedType) {
                $typeResult = $this->resolveParameterFromTypeHint(
                    param: $param,
                    type: $type,
                    className: $className,
                    knownServices: $knownServices,
                    graph: $graph,
                    compiledDefinitions: $compiledDefinitions,
                    autowired: $autowired,
                    reflectionContainer: $reflectionContainer,
                    errors: $errors,
                    warnings: $warnings,
                    contextualArguments: $contextualArguments,
                );

                $resolvedArguments[] = $typeResult;
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $resolvedArguments[] = var_export($param->getDefaultValue(), true);
                continue;
            }

            $errors[] = [
                'errorType' => 'unresolvable_parameter',
                'message' => sprintf(
                    'Parameter "$%s" in "%s::__construct()" cannot be resolved: no type hint, attribute, or default value.',
                    $param->getName(),
                    $className,
                ),
                'suggestedFix' => 'Add a type hint, a default value, or use the #[Inject] attribute.',
            ];
        }

        return [$resolvedArguments, $errors, $warnings];
    }

    /**
     * @param list<string> $knownServices
     * @param list<CompiledDefinition> $compiledDefinitions
     * @param list<string> $autowired
     * @param list<array{errorType: string, message: string, suggestedFix: string}> $errors
     * @param list<string> $warnings
     */
    private function resolveParameterFromAttributes(
        ReflectionParameter $param,
        array &$knownServices,
        DependencyGraph $graph,
        array &$compiledDefinitions,
        array &$autowired,
        ReflectionContainer $reflectionContainer,
        string $ownerClass,
        array &$errors,
        array &$warnings,
    ): ?string {
        foreach ($param->getAttributes() as $attr) {
            $attrClass = $attr->getName();

            if (!is_subclass_of($attrClass, AttributeInterface::class)) {
                continue;
            }

            if ($attrClass === Inject::class || is_subclass_of($attrClass, Inject::class)) {
                $injectId = $attr->getArguments()[0] ?? null;

                if (is_string($injectId)) {
                    [$synthesisErrors, $synthesisWarnings] = $this->ensureSynthesisedIfNeeded(
                        className: $injectId,
                        knownServices: $knownServices,
                        graph: $graph,
                        compiledDefinitions: $compiledDefinitions,
                        autowired: $autowired,
                        reflectionContainer: $reflectionContainer,
                    );

                    array_push($errors, ...$synthesisErrors);
                    array_push($warnings, ...$synthesisWarnings);

                    return sprintf("\$this->get('%s')", addcslashes($injectId, "'\\"));
                }
            }

            if ($attrClass === Resolve::class || is_subclass_of($attrClass, Resolve::class)) {
                $args = $attr->getArguments();
                $resolver = $args[0] ?? ($args['resolver'] ?? null);

                if (is_string($resolver)) {
                    $warnings[] = sprintf(
                        'Parameter "$%s" in "%s::__construct()" uses #[Resolve] which cannot be fully compiled. A simplified expression resolving only the root service "%s" will be emitted. Consider using #[Inject] or explicit arguments instead.',
                        $param->getName(),
                        $ownerClass,
                        $resolver,
                    );

                    [$synthesisErrors, $synthesisWarnings] = $this->ensureSynthesisedIfNeeded(
                        className: $resolver,
                        knownServices: $knownServices,
                        graph: $graph,
                        compiledDefinitions: $compiledDefinitions,
                        autowired: $autowired,
                        reflectionContainer: $reflectionContainer,
                    );

                    array_push($errors, ...$synthesisErrors);
                    array_push($warnings, ...$synthesisWarnings);

                    return sprintf("\$this->get('%s')", addcslashes($resolver, "'\\"));
                }
            }
        }

        return null;
    }

    /**
     * @param list<string> $knownServices
     * @param list<CompiledDefinition> $compiledDefinitions
     * @param list<string> $autowired
     * @param list<array{errorType: string, message: string, suggestedFix: string}> $errors
     * @param list<string> $warnings
     * @param array<string, string|object> $contextualArguments
     */
    private function resolveParameterFromTypeHint(
        ReflectionParameter $param,
        ReflectionNamedType $type,
        string $className,
        array &$knownServices,
        DependencyGraph $graph,
        array &$compiledDefinitions,
        array &$autowired,
        ?ReflectionContainer $reflectionContainer,
        array &$errors,
        array &$warnings,
        array $contextualArguments = [],
    ): string {
        $typeName = $type->getName();

        $normalisedTypeName = Definition::normaliseAlias($typeName);

        if (isset($contextualArguments[$normalisedTypeName])) {
            $contextualConcrete = $contextualArguments[$normalisedTypeName];

            if (is_string($contextualConcrete)) {
                [$synthesisErrors, $synthesisWarnings] = $this->ensureSynthesisedIfNeeded(
                    className: $contextualConcrete,
                    knownServices: $knownServices,
                    graph: $graph,
                    compiledDefinitions: $compiledDefinitions,
                    autowired: $autowired,
                    reflectionContainer: $reflectionContainer,
                );

                array_push($errors, ...$synthesisErrors);
                array_push($warnings, ...$synthesisWarnings);

                return sprintf("\$this->get('%s')", addcslashes($contextualConcrete, "'\\"));
            }
        }

        if ($typeName === 'mixed') {
            $errors[] = [
                'errorType' => 'mixed_type_parameter',
                'message' => sprintf(
                    'Parameter "$%s" in "%s::__construct()" has a mixed type which cannot be autowired.',
                    $param->getName(),
                    $className,
                ),
                'suggestedFix' => 'Use a concrete type hint or the #[Inject] attribute.',
            ];

            return 'null';
        }

        if ($type->isBuiltin()) {
            if ($param->isDefaultValueAvailable()) {
                return var_export($param->getDefaultValue(), true);
            }

            $errors[] = [
                'errorType' => 'unresolvable_builtin_parameter',
                'message' => sprintf(
                    'Parameter "$%s" of type "%s" in "%s::__construct()" is a built-in type with no default value and cannot be autowired.',
                    $param->getName(),
                    $typeName,
                    $className,
                ),
                'suggestedFix' => 'Provide a default value or use explicit arguments when registering the service.',
            ];

            return 'null';
        }

        if (in_array($typeName, $knownServices, strict: true)) {
            return sprintf("\$this->get('%s')", addcslashes($typeName, "'\\"));
        }

        if (interface_exists($typeName) || (class_exists($typeName) && (new ReflectionClass($typeName))->isAbstract())) {
            if ($param->isDefaultValueAvailable()) {
                $defaultValue = $param->getDefaultValue();
                return $defaultValue === null ? 'null' : var_export($defaultValue, true);
            }

            $errors[] = [
                'errorType' => 'unresolvable_interface_parameter',
                'message' => sprintf(
                    'Parameter "$%s" in "%s::__construct()" requires "%s" which is an interface or abstract class with no binding in the container.',
                    $param->getName(),
                    $className,
                    $typeName,
                ),
                'suggestedFix' => sprintf(
                    'Register a concrete implementation for "%s" in the container.',
                    $typeName,
                ),
            ];

            return 'null';
        }

        if (class_exists($typeName)) {
            if ($param->isDefaultValueAvailable()) {
                $defaultValue = $param->getDefaultValue();
                return $defaultValue === null ? 'null' : var_export($defaultValue, true);
            }

            [$synthesisErrors, $synthesisWarnings] = $this->ensureSynthesisedIfNeeded(
                className: $typeName,
                knownServices: $knownServices,
                graph: $graph,
                compiledDefinitions: $compiledDefinitions,
                autowired: $autowired,
                reflectionContainer: $reflectionContainer,
            );

            array_push($errors, ...$synthesisErrors);
            array_push($warnings, ...$synthesisWarnings);

            return sprintf("\$this->get('%s')", addcslashes($typeName, "'\\"));
        }

        if ($param->isDefaultValueAvailable()) {
            $defaultValue = $param->getDefaultValue();
            return $defaultValue === null ? 'null' : var_export($defaultValue, true);
        }

        $errors[] = [
            'errorType' => 'unresolvable_parameter',
            'message' => sprintf(
                'Parameter "$%s" of type "%s" in "%s::__construct()" cannot be resolved: class does not exist and no default value is available.',
                $param->getName(),
                $typeName,
                $className,
            ),
            'suggestedFix' => sprintf(
                'Ensure "%s" exists and is autoloadable, or register it explicitly in the container.',
                $typeName,
            ),
        ];

        return 'null';
    }

    /**
     * @param list<string> $knownServices
     * @param list<CompiledDefinition> $compiledDefinitions
     * @param list<string> $autowired
     * @return array{0: list<array{serviceId: string, errorType: string, message: string, suggestedFix: string}>, 1: list<string>}
     */
    private function ensureSynthesisedIfNeeded(
        string $className,
        array &$knownServices,
        DependencyGraph $graph,
        array &$compiledDefinitions,
        array &$autowired,
        ?ReflectionContainer $reflectionContainer,
    ): array {
        if (in_array($className, $knownServices, strict: true)) {
            return [[], []];
        }

        if (!class_exists($className)) {
            return [[], []];
        }

        $knownServices[] = $className;
        $graph->addNode($className);

        [$transitiveArgs, $transitiveErrors, $transitiveWarnings] = $this->autowireClass(
            className: $className,
            reflectionContainer: $reflectionContainer,
            knownServices: $knownServices,
            graph: $graph,
            compiledDefinitions: $compiledDefinitions,
            autowired: $autowired,
        );

        $errors = [];
        $warnings = $transitiveWarnings;

        foreach ($transitiveErrors as $error) {
            $errors[] = [...$error, 'serviceId' => $className];
        }

        foreach ($transitiveArgs as $expression) {
            if (preg_match('/^\$this->get\(\'([^\']+)\'\)$/', $expression, $matches) === 1) {
                $graph->addEdge($className, stripslashes($matches[1]));
            }
        }

        $reflectionClass = new ReflectionClass($className);
        $isSharedByAttribute = $reflectionClass->getAttributes(Shared::class) !== [];

        $compiledDefinitions[] = new CompiledDefinition(
            id: $className,
            concreteType: ConcreteType::ClassType,
            shared: $isSharedByAttribute,
            resolvedArguments: $transitiveArgs,
            methodCalls: [],
            tags: [],
            concreteClass: $className,
            factoryClass: null,
            factoryMethod: null,
        );

        return [$errors, $warnings];
    }

    private function findReflectionContainerDelegate(Container $container): ?ReflectionContainer
    {
        $property = new ReflectionProperty($container, 'delegates');
        $delegates = $property->getValue($container);

        foreach ($delegates as $delegate) {
            if ($delegate instanceof ReflectionContainer) {
                return $delegate;
            }
        }

        return null;
    }

    private function forceRegisterAllProviders(Container $container): void
    {
        $property = new ReflectionProperty($container, 'providers');
        $providers = $property->getValue($container);
        $providers->registerAll();
    }

    /**
     * @return list<DefinitionInterface>
     */
    private function getDefinitions(Container $container): array
    {
        $property = new ReflectionProperty($container, 'definitions');
        $aggregate = $property->getValue($container);

        $definitions = [];
        foreach ($aggregate as $definition) {
            $definitions[] = $definition;
        }

        return $definitions;
    }

    /**
     * @param list<DefinitionInterface> $definitions
     * @return list<string>
     */
    private function buildKnownServicesList(array $definitions): array
    {
        return array_map(static fn(DefinitionInterface $d): string => $d->getAlias(), $definitions);
    }

    /**
     * @param list<string> $knownServices
     * @return array{concreteType: ConcreteType, concreteClass: ?string, factoryClass: ?string, factoryMethod: ?string, error: ?array{serviceId: string, errorType: string, message: string, suggestedFix: string}}
     */
    private function classifyConcrete(DefinitionInterface $definition, array $knownServices): array
    {
        $concrete = $definition->getConcrete();
        $alias = $definition->getAlias();

        if ($concrete instanceof Closure) {
            return $this->classificationError($alias, 'closure_concrete', sprintf(
                'The concrete for service "%s" is a Closure which cannot be serialised into compiled code.',
                $alias,
            ), 'Replace the Closure with a class name, a [ClassName::class, \'method\'] callable array, or an invokable class. Consider extracting the logic into a dedicated service.');
        }

        if ($concrete instanceof LiteralArgumentInterface) {
            return $this->classifyLiteralArgument($concrete, $alias);
        }

        if (is_object($concrete)) {
            return $this->classificationError($alias, 'object_concrete', sprintf(
                'The concrete for service "%s" is an object instance of "%s" which cannot be serialised into compiled code.',
                $alias,
                get_class($concrete),
            ), sprintf(
                'Register "%s" as a service in the container and reference it by class name instead.',
                get_class($concrete),
            ));
        }

        if (is_array($concrete) && count($concrete) === 2 && is_string($concrete[0]) && is_string($concrete[1])) {
            return $this->classifyArrayCallable($concrete);
        }

        if (is_string($concrete)) {
            return $this->classifyStringConcrete($concrete, $alias, $knownServices);
        }

        return $this->classificationSuccess(ConcreteType::Literal);
    }

    /**
     * @return array{concreteType: ConcreteType, concreteClass: ?string, factoryClass: ?string, factoryMethod: ?string, error: ?array{serviceId: string, errorType: string, message: string, suggestedFix: string}}
     */
    private function classifyLiteralArgument(LiteralArgumentInterface $concrete, string $alias): array
    {
        if ($concrete instanceof ObjectArgument) {
            return $this->classificationError($alias, 'object_concrete', sprintf(
                'The concrete for service "%s" is an object instance of "%s" which cannot be serialised into compiled code.',
                $alias,
                get_class($concrete->getValue()),
            ), sprintf(
                'Register "%s" as a service in the container and reference it by class name instead.',
                get_class($concrete->getValue()),
            ));
        }

        return $this->classificationSuccess(ConcreteType::Literal);
    }

    /**
     * @param array{0: string, 1: string} $callable
     * @return array{concreteType: ConcreteType, concreteClass: ?string, factoryClass: ?string, factoryMethod: ?string, error: ?array{serviceId: string, errorType: string, message: string, suggestedFix: string}}
     */
    private function classifyArrayCallable(array $callable): array
    {
        [$className, $methodName] = $callable;

        if (!class_exists($className)) {
            return $this->classificationSuccess(ConcreteType::StaticCallable, factoryClass: $className, factoryMethod: $methodName);
        }

        $reflection = new ReflectionMethod($className, $methodName);

        if ($reflection->isStatic()) {
            return $this->classificationSuccess(ConcreteType::StaticCallable, factoryClass: Definition::normaliseAlias($className), factoryMethod: $methodName);
        }

        return $this->classificationSuccess(ConcreteType::InstanceCallable, factoryClass: Definition::normaliseAlias($className), factoryMethod: $methodName);
    }

    /**
     * @param list<string> $knownServices
     * @return array{concreteType: ConcreteType, concreteClass: ?string, factoryClass: ?string, factoryMethod: ?string, error: ?array{serviceId: string, errorType: string, message: string, suggestedFix: string}}
     */
    private function classifyStringConcrete(string $concrete, string $alias, array $knownServices): array
    {
        $normalisedConcrete = Definition::normaliseAlias($concrete);

        if ($normalisedConcrete === $alias && class_exists($concrete)) {
            return $this->classificationSuccess(ConcreteType::ClassType, concreteClass: $normalisedConcrete);
        }

        if (in_array($normalisedConcrete, $knownServices, strict: true)) {
            return $this->classificationSuccess(ConcreteType::Alias, concreteClass: $normalisedConcrete);
        }

        if (class_exists($concrete) && method_exists($concrete, '__invoke')) {
            return $this->classificationSuccess(ConcreteType::Invokable, concreteClass: $normalisedConcrete);
        }

        if (class_exists($concrete)) {
            return $this->classificationSuccess(ConcreteType::ClassType, concreteClass: $normalisedConcrete);
        }

        return $this->classificationSuccess(ConcreteType::Literal);
    }

    /**
     * @return array{concreteType: ConcreteType, concreteClass: ?string, factoryClass: ?string, factoryMethod: ?string, error: null}
     */
    private function classificationSuccess(
        ConcreteType $concreteType,
        ?string $concreteClass = null,
        ?string $factoryClass = null,
        ?string $factoryMethod = null,
    ): array {
        return [
            'concreteType' => $concreteType,
            'concreteClass' => $concreteClass,
            'factoryClass' => $factoryClass,
            'factoryMethod' => $factoryMethod,
            'error' => null,
        ];
    }

    /**
     * @return array{concreteType: ConcreteType, concreteClass: null, factoryClass: null, factoryMethod: null, error: array{serviceId: string, errorType: string, message: string, suggestedFix: string}}
     */
    private function classificationError(string $serviceId, string $errorType, string $message, string $suggestedFix): array
    {
        return [
            'concreteType' => ConcreteType::Literal,
            'concreteClass' => null,
            'factoryClass' => null,
            'factoryMethod' => null,
            'error' => [
                'serviceId' => $serviceId,
                'errorType' => $errorType,
                'message' => $message,
                'suggestedFix' => $suggestedFix,
            ],
        ];
    }

    /**
     * @param array<int, mixed> $arguments
     * @param list<string> $knownServices
     * @return array{0: list<string>, 1: list<array{serviceId: string, errorType: string, message: string, suggestedFix: string}>}
     */
    private function compileArguments(array $arguments, array $knownServices, string $serviceId): array
    {
        $resolved = [];
        $errors = [];

        foreach (array_values($arguments) as $position => $argument) {
            try {
                $resolved[] = $this->argumentCompiler->compile($argument, $knownServices, $serviceId, $position);
            } catch (CompilationException $e) {
                foreach ($e->getErrors() as $error) {
                    $errors[] = $error;
                }
            }
        }

        return [$resolved, $errors];
    }

    /**
     * @param list<array{method: string, arguments: array<int, mixed>}> $methodCalls
     * @param list<string> $knownServices
     * @return array{0: list<array{method: string, arguments: list<string>}>, 1: list<array{serviceId: string, errorType: string, message: string, suggestedFix: string}>}
     */
    private function compileMethodCalls(array $methodCalls, array $knownServices, string $serviceId): array
    {
        $compiled = [];
        $errors = [];

        foreach ($methodCalls as $call) {
            [$compiledArgs, $callErrors] = $this->compileArguments($call['arguments'], $knownServices, $serviceId);

            foreach ($callErrors as $error) {
                $errors[] = $error;
            }

            $compiled[] = [
                'method' => $call['method'],
                'arguments' => $compiledArgs,
            ];
        }

        return [$compiled, $errors];
    }

    /**
     * @param list<string> $resolvedArguments
     */
    private function registerDependencyEdges(DependencyGraph $graph, string $serviceId, array $resolvedArguments): void
    {
        foreach ($resolvedArguments as $expression) {
            if (preg_match('/^\$this->get\(\'([^\']+)\'\)$/', $expression, $matches) === 1) {
                $graph->addEdge($serviceId, stripslashes($matches[1]));
            }
        }
    }

    /**
     * @param list<array{method: string, arguments: list<string>}> $methodCalls
     * @return list<string>
     */
    private function flattenMethodArguments(array $methodCalls): array
    {
        $all = [];

        foreach ($methodCalls as $call) {
            foreach ($call['arguments'] as $arg) {
                $all[] = $arg;
            }
        }

        return $all;
    }

    /**
     * @param array<string, list<string>> $tagMap
     * @param list<string> $knownServices
     * @param list<string> $warnings
     */
    private function detectTagServiceIdCollisions(array $tagMap, array $knownServices, array &$warnings): void
    {
        foreach (array_keys($tagMap) as $tag) {
            if (in_array($tag, $knownServices, strict: true)) {
                $warnings[] = sprintf(
                    'Tag "%s" collides with a service ID of the same name. This may cause unexpected behaviour when resolving by tag.',
                    $tag,
                );
            }
        }
    }

    /**
     * @param list<string> $warnings
     */
    private function detectEventListeners(Container $container, array &$warnings): void
    {
        $dispatcher = $container->getEventDispatcher();

        if ($dispatcher === null) {
            return;
        }

        if ($dispatcher->getListeners() !== [] || $dispatcher->getFilters() !== []) {
            $warnings[] = 'The container has event listeners or filters registered. These cannot be compiled and will not be active in the compiled container.';
        }
    }

    /**
     * @param list<string> $warnings
     */
    private function detectNonReflectionDelegates(Container $container, array &$warnings): void
    {
        $property = new ReflectionProperty($container, 'delegates');
        $delegates = $property->getValue($container);

        foreach ($delegates as $delegate) {
            if (!$delegate instanceof ReflectionContainer) {
                $warnings[] = sprintf(
                    'Delegate container of type "%s" is not a ReflectionContainer and will not be available in the compiled container.',
                    get_class($delegate),
                );
            }
        }
    }
}
