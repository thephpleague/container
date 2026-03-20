<?php

declare(strict_types=1);

use League\Container\Compiler\CodeGenerator;
use League\Container\Compiler\CompilationConfig;
use League\Container\Compiler\CompilationException;
use League\Container\Compiler\CompiledDefinition;
use League\Container\Compiler\ConcreteType;
use League\Container\Compiler\DependencyGraph;

function makeGraph(CompiledDefinition ...$definitions): DependencyGraph
{
    $graph = new DependencyGraph();

    foreach ($definitions as $definition) {
        $graph->addNode($definition->id);
    }

    return $graph;
}

/**
 * @param list<string> $resolvedArguments
 * @param list<array{method: string, arguments: list<string>}> $methodCalls
 * @param list<string> $tags
 */
function makeDefinition(
    string $id,
    ConcreteType $concreteType = ConcreteType::ClassType,
    bool $shared = true,
    array $resolvedArguments = [],
    array $methodCalls = [],
    array $tags = [],
    ?string $concreteClass = null,
    ?string $factoryClass = null,
    ?string $factoryMethod = null,
): CompiledDefinition {
    return new CompiledDefinition(
        id: $id,
        concreteType: $concreteType,
        shared: $shared,
        resolvedArguments: $resolvedArguments,
        methodCalls: $methodCalls,
        tags: $tags,
        concreteClass: $concreteClass ?? $id,
        factoryClass: $factoryClass,
        factoryMethod: $factoryMethod,
    );
}

test('generated output starts with php open tag and strict types declaration', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc123');

    expect($source)->toStartWith("<?php\n\ndeclare(strict_types=1);\n");
});

test('generated class is final and implements ContainerInterface', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc123');

    expect($source)->toContain('final class CompiledContainer implements ContainerInterface');
});

test('namespace is emitted when config provides one', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig(namespace: 'MyApp\\Generated');

    $source = $generator->generate([$definition], $graph, $config, 'abc123');

    expect($source)->toContain('namespace MyApp\\Generated;');
});

test('namespace is omitted when config namespace is empty', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig(namespace: '');

    $source = $generator->generate([$definition], $graph, $config, 'abc123');

    expect($source)->not->toContain('namespace ;');
});

test('generated class uses custom class name from config', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig(className: 'MyCustomContainer');

    $source = $generator->generate([$definition], $graph, $config, 'abc123');

    expect($source)->toContain('final class MyCustomContainer implements ContainerInterface');
});

test('generated code imports ContainerInterface and NotFoundException', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc123');

    expect($source)->toContain('use Psr\\Container\\ContainerInterface;');
    expect($source)->not->toContain('use League\\Container\\Exception\\NotFoundException;');
});

test('class constants are present with correct values', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'deadbeef');

    expect($source)->toContain("public const string SOURCE_HASH = 'deadbeef';");
    expect($source)->toContain("public const string COMPILER_VERSION = '1.0.0';");
    expect($source)->toContain('public const int SERVICE_COUNT = 1;');
    expect($source)->toContain('public const string COMPILED_AT =');
});

test('service count constant reflects number of definitions', function () {
    $generator = new CodeGenerator();
    $definitions = [
        makeDefinition('App\\ServiceA'),
        makeDefinition('App\\ServiceB'),
        makeDefinition('App\\ServiceC'),
    ];
    $graph = makeGraph(...$definitions);
    $config = new CompilationConfig();

    $source = $generator->generate($definitions, $graph, $config, 'abc');

    expect($source)->toContain('public const int SERVICE_COUNT = 3;');
});

test('shared private array property is present', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('private array $shared = [];');
});

test('get method uses nullsafe assignment for shared services', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service', shared: true);
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("\$this->shared['App\\\\Service'] ??= \$this->createApp_Service()");
});

test('get method calls factory directly for non-shared services', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service', shared: false);
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("'App\\\\Service' => \$this->createApp_Service(),");
    expect($source)->not->toContain('??=');
});

test('get method default arm throws NotFoundException', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('throw new \\League\\Container\\Exception\\NotFoundException(');
    expect($source)->toContain('is not compiled in this container.');
});

test('has method returns true for known service ids', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("'App\\\\Service' => true,");
});

test('has method returns false as default', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('default => false,');
});

test('class type generates new instantiation with backslash prefix', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service', ConcreteType::ClassType, concreteClass: 'App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('return new \\App\\Service()');
});

test('class type with resolved arguments passes them to constructor', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\Service',
        ConcreteType::ClassType,
        resolvedArguments: ["\$this->get('App\\\\Logger')", "'config-value'"],
        concreteClass: 'App\\Service',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("return new \\App\\Service(\$this->get('App\\\\Logger'), 'config-value')");
});

test('alias type generates get call to concrete class', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\ServiceInterface',
        ConcreteType::Alias,
        concreteClass: 'App\\ServiceImpl',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('return $this->get(\\App\\ServiceImpl::class)');
});

test('literal type returns first resolved argument', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'config.db.host',
        ConcreteType::Literal,
        resolvedArguments: ["'localhost'"],
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("return 'localhost';");
});

test('literal type with no arguments returns null', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'config.empty',
        ConcreteType::Literal,
        resolvedArguments: [],
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('return null;');
});

test('static callable type generates static method call', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\Service',
        ConcreteType::StaticCallable,
        factoryClass: 'App\\ServiceFactory',
        factoryMethod: 'create',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('return \\App\\ServiceFactory::create()');
});

test('static callable type with arguments passes them to the static method', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\Service',
        ConcreteType::StaticCallable,
        resolvedArguments: ["'arg1'"],
        factoryClass: 'App\\ServiceFactory',
        factoryMethod: 'create',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("return \\App\\ServiceFactory::create('arg1')");
});

test('instance callable type generates get plus method call', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\Service',
        ConcreteType::InstanceCallable,
        factoryClass: 'App\\ServiceFactory',
        factoryMethod: 'create',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('return $this->get(\\App\\ServiceFactory::class)->create()');
});

test('instance callable type with arguments passes them to the method', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\Service',
        ConcreteType::InstanceCallable,
        resolvedArguments: ["'arg1'", "'arg2'"],
        factoryClass: 'App\\ServiceFactory',
        factoryMethod: 'build',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("return \$this->get(\\App\\ServiceFactory::class)->build('arg1', 'arg2')");
});

test('invokable type generates new instance and invoke call', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\InvokableService',
        ConcreteType::Invokable,
        concreteClass: 'App\\InvokableService',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('return (new \\App\\InvokableService())()');
});

test('invokable type with constructor arguments passes them correctly', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\InvokableService',
        ConcreteType::Invokable,
        resolvedArguments: ["'param1'"],
        concreteClass: 'App\\InvokableService',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("return (new \\App\\InvokableService('param1'))()");
});

test('method calls are generated after instantiation', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\Service',
        ConcreteType::ClassType,
        methodCalls: [
            ['method' => 'setLogger', 'arguments' => ["\$this->get('App\\\\Logger')"]],
        ],
        concreteClass: 'App\\Service',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('$instance = new \\App\\Service()');
    expect($source)->toContain("\$instance->setLogger(\$this->get('App\\\\Logger'))");
    expect($source)->toContain('return $instance;');
});

test('multiple method calls are all generated in sequence', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\Service',
        ConcreteType::ClassType,
        methodCalls: [
            ['method' => 'setLogger', 'arguments' => ["'logger'"]],
            ['method' => 'setDebug', 'arguments' => ['true']],
        ],
        concreteClass: 'App\\Service',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("\$instance->setLogger('logger')");
    expect($source)->toContain('$instance->setDebug(true)');
});

test('tagged services produce tag factory method returning array of get calls', function () {
    $generator = new CodeGenerator();
    $definitions = [
        makeDefinition('App\\HandlerA', tags: ['handler']),
        makeDefinition('App\\HandlerB', tags: ['handler']),
    ];
    $graph = makeGraph(...$definitions);
    $config = new CompilationConfig();

    $source = $generator->generate($definitions, $graph, $config, 'abc');

    expect($source)->toContain('createTag_handler');
    expect($source)->toContain("\$this->get('App\\\\HandlerA')");
    expect($source)->toContain("\$this->get('App\\\\HandlerB')");
});

test('tag method is accessible via get using tag name', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service', tags: ['my_tag']);
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("'my_tag' => \$this->createTag_my_tag()");
});

test('tag id is included in has method', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service', tags: ['my_tag']);
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain("'my_tag'");
});

test('service id takes priority over tag id when they collide in get method', function () {
    $generator = new CodeGenerator();
    $definitions = [
        makeDefinition('collision', ConcreteType::ClassType, concreteClass: 'App\\Collision'),
        makeDefinition('App\\Tagged', tags: ['collision']),
    ];
    $graph = makeGraph(...$definitions);
    $config = new CompilationConfig();

    $source = $generator->generate($definitions, $graph, $config, 'abc');

    expect($source)->toContain("'collision' => \$this->shared['collision']");
    expect($source)->not->toContain("'collision' => \$this->createTag_collision()");
});

test('method name converts backslashes to underscores', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Deep\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('createApp_Deep_Service');
});

test('method name converts dots to underscores', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('config.db.host', ConcreteType::Literal, resolvedArguments: ["'localhost'"]);
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('createconfig_db_host');
});

test('duplicate method names receive numeric suffix to ensure uniqueness', function () {
    $generator = new CodeGenerator();
    $definitions = [
        makeDefinition('App\\Service', ConcreteType::ClassType, concreteClass: 'App\\Service'),
        makeDefinition('App/Service', ConcreteType::ClassType, concreteClass: 'App\\Service'),
    ];
    $graph = makeGraph(...$definitions);
    $config = new CompilationConfig();

    $source = $generator->generate($definitions, $graph, $config, 'abc');

    expect($source)->toContain('createApp_Service()');
    expect($source)->toContain('createApp_Service_2()');
});

test('generated source is syntactically valid php', function () {
    $generator = new CodeGenerator();
    $definitions = [
        makeDefinition('App\\ServiceA', shared: true),
        makeDefinition('App\\ServiceB', shared: false, resolvedArguments: ["\$this->get('App\\\\ServiceA')"]),
        makeDefinition('config.value', ConcreteType::Literal, resolvedArguments: ["'test'"]),
    ];
    $graph = makeGraph(...$definitions);
    $config = new CompilationConfig(namespace: 'Generated', className: 'MyContainer');

    $source = $generator->generate($definitions, $graph, $config, 'testhash');

    $tempFile = tempnam(sys_get_temp_dir(), 'container_test_') . '.php';
    file_put_contents($tempFile, $source);

    $output = shell_exec("php -l {$tempFile} 2>&1");
    unlink($tempFile);

    expect($output)->toContain('No syntax errors detected');
});

test('generated source with all concrete types is syntactically valid php', function () {
    $generator = new CodeGenerator();
    $definitions = [
        makeDefinition('App\\ClassService', ConcreteType::ClassType, shared: true, concreteClass: 'App\\ClassService'),
        makeDefinition('App\\AliasService', ConcreteType::Alias, shared: false, concreteClass: 'App\\ClassService'),
        makeDefinition('config.literal', ConcreteType::Literal, shared: false, resolvedArguments: ['42']),
        makeDefinition('App\\StaticService', ConcreteType::StaticCallable, shared: false, factoryClass: 'App\\Factory', factoryMethod: 'make'),
        makeDefinition('App\\InstanceService', ConcreteType::InstanceCallable, shared: false, factoryClass: 'App\\Factory', factoryMethod: 'build'),
        makeDefinition('App\\Invokable', ConcreteType::Invokable, shared: false, concreteClass: 'App\\Invokable'),
    ];
    $graph = makeGraph(...$definitions);
    $config = new CompilationConfig();

    $source = $generator->generate($definitions, $graph, $config, 'allhash');

    $tempFile = tempnam(sys_get_temp_dir(), 'container_test_') . '.php';
    file_put_contents($tempFile, $source);

    $output = shell_exec("php -l {$tempFile} 2>&1");
    unlink($tempFile);

    expect($output)->toContain('No syntax errors detected');
});

test('generated source with tags is syntactically valid php', function () {
    $generator = new CodeGenerator();
    $definitions = [
        makeDefinition('App\\HandlerA', tags: ['handlers']),
        makeDefinition('App\\HandlerB', tags: ['handlers', 'secondary']),
    ];
    $graph = makeGraph(...$definitions);
    $config = new CompilationConfig();

    $source = $generator->generate($definitions, $graph, $config, 'taghash');

    $tempFile = tempnam(sys_get_temp_dir(), 'container_test_') . '.php';
    file_put_contents($tempFile, $source);

    $output = shell_exec("php -l {$tempFile} 2>&1");
    unlink($tempFile);

    expect($output)->toContain('No syntax errors detected');
});

test('generated source with method calls is syntactically valid php', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition(
        'App\\Service',
        ConcreteType::ClassType,
        methodCalls: [
            ['method' => 'setup', 'arguments' => ["'value'", 'true']],
        ],
        concreteClass: 'App\\Service',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    $tempFile = tempnam(sys_get_temp_dir(), 'container_test_') . '.php';
    file_put_contents($tempFile, $source);

    $output = shell_exec("php -l {$tempFile} 2>&1");
    unlink($tempFile);

    expect($output)->toContain('No syntax errors detected');
});

test('leading backslash on concrete class is stripped before re-prefixing', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service', concreteClass: '\\App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('new \\App\\Service()');
    expect($source)->not->toContain('new \\\\App\\Service()');
});

test('empty definitions list generates valid class with no service arms', function () {
    $generator = new CodeGenerator();
    $graph = new DependencyGraph();
    $config = new CompilationConfig();

    $source = $generator->generate([], $graph, $config, 'emptyhash');

    $tempFile = tempnam(sys_get_temp_dir(), 'container_test_') . '.php';
    file_put_contents($tempFile, $source);

    $output = shell_exec("php -l {$tempFile} 2>&1");
    unlink($tempFile);

    expect($output)->toContain('No syntax errors detected');
    expect($source)->toContain('public const int SERVICE_COUNT = 0;');
});

test('service with multiple tags appears in each tag factory method', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service', tags: ['tagA', 'tagB']);
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, 'abc');

    expect($source)->toContain('createTag_tagA');
    expect($source)->toContain('createTag_tagB');
});

test('multiple services with same tag all appear in that tag factory method', function () {
    $generator = new CodeGenerator();
    $definitions = [
        makeDefinition('App\\First', tags: ['shared_tag']),
        makeDefinition('App\\Second', tags: ['shared_tag']),
        makeDefinition('App\\Third', tags: ['shared_tag']),
    ];
    $graph = makeGraph(...$definitions);
    $config = new CompilationConfig();

    $source = $generator->generate($definitions, $graph, $config, 'abc');

    $tagMethodStart = strpos($source, 'createTag_shared_tag');
    assert($tagMethodStart !== false);

    $tagMethodRegion = substr($source, $tagMethodStart);

    expect($tagMethodRegion)->toContain("'App\\\\First'");
    expect($tagMethodRegion)->toContain("'App\\\\Second'");
    expect($tagMethodRegion)->toContain("'App\\\\Third'");
});

test('alias definition with null concrete class throws compilation exception', function () {
    $generator = new CodeGenerator();
    $definition = new CompiledDefinition(
        id: 'App\\ServiceInterface',
        concreteType: ConcreteType::Alias,
        shared: false,
        resolvedArguments: [],
        methodCalls: [],
        tags: [],
        concreteClass: null,
        factoryClass: null,
        factoryMethod: null,
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    expect(fn() => $generator->generate([$definition], $graph, $config, 'abc'))
        ->toThrow(CompilationException::class, 'concreteClass');
});

test('static callable definition with null factory class throws compilation exception', function () {
    $generator = new CodeGenerator();
    $definition = new CompiledDefinition(
        id: 'App\\Service',
        concreteType: ConcreteType::StaticCallable,
        shared: false,
        resolvedArguments: [],
        methodCalls: [],
        tags: [],
        concreteClass: null,
        factoryClass: null,
        factoryMethod: 'create',
    );
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    expect(fn() => $generator->generate([$definition], $graph, $config, 'abc'))
        ->toThrow(CompilationException::class, 'factoryClass');
});

test('source hash with special characters is safely escaped in generated constant', function () {
    $generator = new CodeGenerator();
    $definition = makeDefinition('App\\Service');
    $graph = makeGraph($definition);
    $config = new CompilationConfig();

    $source = $generator->generate([$definition], $graph, $config, "hash'with\\special");

    expect($source)->toContain("SOURCE_HASH = 'hash\\'with\\\\special';");
});
