<?php

declare(strict_types=1);

use League\Container\Compiler\DependencyGraph;
use League\Container\Exception\ContainerException;

test('no cycles detected in linear chain', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'C');

    expect($graph->detectCycles())->toBe([]);
});

test('no cycles detected in empty graph', function () {
    $graph = new DependencyGraph();

    expect($graph->detectCycles())->toBe([]);
});

test('no cycles detected with isolated nodes', function () {
    $graph = new DependencyGraph();
    $graph->addNode('A');
    $graph->addNode('B');

    expect($graph->detectCycles())->toBe([]);
});

test('self referencing node is detected as cycle', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'A');

    $cycles = $graph->detectCycles();

    expect($cycles)->toHaveCount(1);
    expect($cycles[0])->toBe(['A', 'A']);
});

test('direct two node cycle is detected', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'A');

    $cycles = $graph->detectCycles();

    expect($cycles)->toHaveCount(1);
    expect($cycles[0])->toContain('A');
    expect($cycles[0])->toContain('B');
});

test('three node cycle is detected with full path', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'C');
    $graph->addEdge('C', 'A');

    $cycles = $graph->detectCycles();

    expect($cycles)->toHaveCount(1);
    expect($cycles[0])->toContain('A');
    expect($cycles[0])->toContain('B');
    expect($cycles[0])->toContain('C');
});

test('multiple independent cycles are all detected', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'A');
    $graph->addEdge('X', 'Y');
    $graph->addEdge('Y', 'X');

    $cycles = $graph->detectCycles();

    expect($cycles)->toHaveCount(2);

    $allNodes = array_merge(...$cycles);
    expect($allNodes)->toContain('A');
    expect($allNodes)->toContain('B');
    expect($allNodes)->toContain('X');
    expect($allNodes)->toContain('Y');
});

test('diamond dependency has no cycles', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('A', 'C');
    $graph->addEdge('B', 'D');
    $graph->addEdge('C', 'D');

    expect($graph->detectCycles())->toBe([]);
});

test('topological order produces valid dependency ordering', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'C');

    $order = $graph->getTopologicalOrder();

    expect($order)->toContain('A');
    expect($order)->toContain('B');
    expect($order)->toContain('C');
    expect(array_search('A', $order))->toBeLessThan(array_search('B', $order));
    expect(array_search('B', $order))->toBeLessThan(array_search('C', $order));
});

test('topological order with diamond dependency', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('A', 'C');
    $graph->addEdge('B', 'D');
    $graph->addEdge('C', 'D');

    $order = $graph->getTopologicalOrder();

    expect($order)->toHaveCount(4);
    expect(array_search('A', $order))->toBeLessThan(array_search('B', $order));
    expect(array_search('A', $order))->toBeLessThan(array_search('C', $order));
    expect(array_search('B', $order))->toBeLessThan(array_search('D', $order));
    expect(array_search('C', $order))->toBeLessThan(array_search('D', $order));
});

test('topological order returns all nodes for empty graph', function () {
    $graph = new DependencyGraph();
    $graph->addNode('A');
    $graph->addNode('B');

    $order = $graph->getTopologicalOrder();

    expect($order)->toHaveCount(2);
    expect($order)->toContain('A');
    expect($order)->toContain('B');
});

test('get dependencies returns direct dependencies only', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('A', 'C');
    $graph->addEdge('B', 'D');

    expect($graph->getDependencies('A'))->toBe(['B', 'C']);
});

test('get dependencies returns empty array for node with no dependencies', function () {
    $graph = new DependencyGraph();
    $graph->addNode('A');

    expect($graph->getDependencies('A'))->toBe([]);
});

test('get dependencies returns empty array for unknown node', function () {
    $graph = new DependencyGraph();

    expect($graph->getDependencies('unknown'))->toBe([]);
});

test('get transitive dependencies returns all reachable dependencies', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'C');
    $graph->addEdge('C', 'D');

    $transitive = $graph->getTransitiveDependencies('A');

    expect($transitive)->toContain('B');
    expect($transitive)->toContain('C');
    expect($transitive)->toContain('D');
    expect($transitive)->not->toContain('A');
});

test('get transitive dependencies with diamond does not duplicate shared dependency', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('A', 'C');
    $graph->addEdge('B', 'D');
    $graph->addEdge('C', 'D');

    $transitive = $graph->getTransitiveDependencies('A');

    expect($transitive)->toHaveCount(3);
    expect($transitive)->toContain('B');
    expect($transitive)->toContain('C');
    expect($transitive)->toContain('D');
});

test('get transitive dependencies returns empty array for unknown node', function () {
    $graph = new DependencyGraph();

    expect($graph->getTransitiveDependencies('unknown'))->toBe([]);
});

test('get transitive dependencies returns empty array for leaf node', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');

    expect($graph->getTransitiveDependencies('B'))->toBe([]);
});

test('add edge automatically registers nodes if not previously added', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');

    expect($graph->getDependencies('A'))->toBe(['B']);
    expect($graph->getDependencies('B'))->toBe([]);
});

test('add edge does not create duplicate dependencies', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('A', 'B');

    expect($graph->getDependencies('A'))->toBe(['B']);
});

test('depth guard throws when cycle detection exceeds configured limit', function () {
    $graph = new DependencyGraph(depthGuard: 3);

    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'C');
    $graph->addEdge('C', 'D');
    $graph->addEdge('D', 'E');

    expect(fn() => $graph->detectCycles())
        ->toThrow(ContainerException::class, 'Dependency graph depth guard of 3 exceeded');
});

test('depth guard throws when topological order exceeds configured limit', function () {
    $graph = new DependencyGraph(depthGuard: 3);

    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'C');
    $graph->addEdge('C', 'D');

    expect(fn() => $graph->getTopologicalOrder())
        ->toThrow(ContainerException::class, 'Dependency graph depth guard of 3 exceeded');
});

test('depth guard throws when transitive dependency traversal exceeds configured limit', function () {
    $graph = new DependencyGraph(depthGuard: 3);

    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'C');
    $graph->addEdge('C', 'D');
    $graph->addEdge('D', 'E');

    expect(fn() => $graph->getTransitiveDependencies('A'))
        ->toThrow(ContainerException::class, 'Dependency graph depth guard of 3 exceeded');
});

test('topological order throws when graph contains cycles', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'A');

    expect(fn() => $graph->getTopologicalOrder())
        ->toThrow(ContainerException::class, 'Cannot produce a topological ordering');
});

test('get transitive dependencies handles cyclic graph without infinite recursion', function () {
    $graph = new DependencyGraph();
    $graph->addEdge('A', 'B');
    $graph->addEdge('B', 'A');

    $transitive = $graph->getTransitiveDependencies('A');

    expect($transitive)->toContain('B');
    expect($transitive)->not->toContain('A');
});

test('default depth guard is configured at fifty', function () {
    $graph = new DependencyGraph();

    for ($i = 0; $i < 49; $i++) {
        $graph->addEdge((string) $i, (string) ($i + 1));
    }

    expect($graph->getTopologicalOrder())->toHaveCount(50);
});
