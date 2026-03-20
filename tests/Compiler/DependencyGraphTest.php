<?php

declare(strict_types=1);

namespace League\Container\Test\Compiler;

use League\Container\Compiler\DependencyGraph;
use League\Container\Exception\ContainerException;
use PHPUnit\Framework\TestCase;

class DependencyGraphTest extends TestCase
{
    public function testNoCyclesDetectedInLinearChain(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'C');

        $this->assertSame([], $graph->detectCycles());
    }

    public function testNoCyclesDetectedInEmptyGraph(): void
    {
        $graph = new DependencyGraph();

        $this->assertSame([], $graph->detectCycles());
    }

    public function testNoCyclesDetectedWithIsolatedNodes(): void
    {
        $graph = new DependencyGraph();
        $graph->addNode('A');
        $graph->addNode('B');

        $this->assertSame([], $graph->detectCycles());
    }

    public function testSelfReferencingNodeIsDetectedAsCycle(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'A');

        $cycles = $graph->detectCycles();

        $this->assertCount(1, $cycles);
        $this->assertSame(['A', 'A'], $cycles[0]);
    }

    public function testDirectTwoNodeCycleIsDetected(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'A');

        $cycles = $graph->detectCycles();

        $this->assertCount(1, $cycles);
        $this->assertContains('A', $cycles[0]);
        $this->assertContains('B', $cycles[0]);
    }

    public function testThreeNodeCycleIsDetectedWithFullPath(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'C');
        $graph->addEdge('C', 'A');

        $cycles = $graph->detectCycles();

        $this->assertCount(1, $cycles);
        $this->assertContains('A', $cycles[0]);
        $this->assertContains('B', $cycles[0]);
        $this->assertContains('C', $cycles[0]);
    }

    public function testMultipleIndependentCyclesAreAllDetected(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'A');
        $graph->addEdge('X', 'Y');
        $graph->addEdge('Y', 'X');

        $cycles = $graph->detectCycles();

        $this->assertCount(2, $cycles);

        $allNodes = array_merge(...$cycles);
        $this->assertContains('A', $allNodes);
        $this->assertContains('B', $allNodes);
        $this->assertContains('X', $allNodes);
        $this->assertContains('Y', $allNodes);
    }

    public function testDiamondDependencyHasNoCycles(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('A', 'C');
        $graph->addEdge('B', 'D');
        $graph->addEdge('C', 'D');

        $this->assertSame([], $graph->detectCycles());
    }

    public function testTopologicalOrderProducesValidDependencyOrdering(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'C');

        $order = $graph->getTopologicalOrder();

        $this->assertContains('A', $order);
        $this->assertContains('B', $order);
        $this->assertContains('C', $order);
        $this->assertLessThan(array_search('B', $order), array_search('A', $order));
        $this->assertLessThan(array_search('C', $order), array_search('B', $order));
    }

    public function testTopologicalOrderWithDiamondDependency(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('A', 'C');
        $graph->addEdge('B', 'D');
        $graph->addEdge('C', 'D');

        $order = $graph->getTopologicalOrder();

        $this->assertCount(4, $order);
        $this->assertLessThan(array_search('B', $order), array_search('A', $order));
        $this->assertLessThan(array_search('C', $order), array_search('A', $order));
        $this->assertLessThan(array_search('D', $order), array_search('B', $order));
        $this->assertLessThan(array_search('D', $order), array_search('C', $order));
    }

    public function testTopologicalOrderReturnsAllNodesForEmptyGraph(): void
    {
        $graph = new DependencyGraph();
        $graph->addNode('A');
        $graph->addNode('B');

        $order = $graph->getTopologicalOrder();

        $this->assertCount(2, $order);
        $this->assertContains('A', $order);
        $this->assertContains('B', $order);
    }

    public function testGetDependenciesReturnsDirectDependenciesOnly(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('A', 'C');
        $graph->addEdge('B', 'D');

        $this->assertSame(['B', 'C'], $graph->getDependencies('A'));
    }

    public function testGetDependenciesReturnsEmptyArrayForNodeWithNoDependencies(): void
    {
        $graph = new DependencyGraph();
        $graph->addNode('A');

        $this->assertSame([], $graph->getDependencies('A'));
    }

    public function testGetDependenciesReturnsEmptyArrayForUnknownNode(): void
    {
        $graph = new DependencyGraph();

        $this->assertSame([], $graph->getDependencies('unknown'));
    }

    public function testGetTransitiveDependenciesReturnsAllReachableDependencies(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'C');
        $graph->addEdge('C', 'D');

        $transitive = $graph->getTransitiveDependencies('A');

        $this->assertContains('B', $transitive);
        $this->assertContains('C', $transitive);
        $this->assertContains('D', $transitive);
        $this->assertNotContains('A', $transitive);
    }

    public function testGetTransitiveDependenciesWithDiamondDoesNotDuplicateSharedDependency(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('A', 'C');
        $graph->addEdge('B', 'D');
        $graph->addEdge('C', 'D');

        $transitive = $graph->getTransitiveDependencies('A');

        $this->assertCount(3, $transitive);
        $this->assertContains('B', $transitive);
        $this->assertContains('C', $transitive);
        $this->assertContains('D', $transitive);
    }

    public function testGetTransitiveDependenciesReturnsEmptyArrayForUnknownNode(): void
    {
        $graph = new DependencyGraph();

        $this->assertSame([], $graph->getTransitiveDependencies('unknown'));
    }

    public function testGetTransitiveDependenciesReturnsEmptyArrayForLeafNode(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');

        $this->assertSame([], $graph->getTransitiveDependencies('B'));
    }

    public function testAddEdgeAutomaticallyRegistersNodesIfNotPreviouslyAdded(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');

        $this->assertSame(['B'], $graph->getDependencies('A'));
        $this->assertSame([], $graph->getDependencies('B'));
    }

    public function testAddEdgeDoesNotCreateDuplicateDependencies(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('A', 'B');

        $this->assertSame(['B'], $graph->getDependencies('A'));
    }

    public function testDepthGuardThrowsWhenCycleDetectionExceedsConfiguredLimit(): void
    {
        $graph = new DependencyGraph(depthGuard: 3);

        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'C');
        $graph->addEdge('C', 'D');
        $graph->addEdge('D', 'E');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Dependency graph depth guard of 3 exceeded');

        $graph->detectCycles();
    }

    public function testDepthGuardThrowsWhenTopologicalOrderExceedsConfiguredLimit(): void
    {
        $graph = new DependencyGraph(depthGuard: 3);

        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'C');
        $graph->addEdge('C', 'D');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Dependency graph depth guard of 3 exceeded');

        $graph->getTopologicalOrder();
    }

    public function testDepthGuardThrowsWhenTransitiveDependencyTraversalExceedsConfiguredLimit(): void
    {
        $graph = new DependencyGraph(depthGuard: 3);

        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'C');
        $graph->addEdge('C', 'D');
        $graph->addEdge('D', 'E');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Dependency graph depth guard of 3 exceeded');

        $graph->getTransitiveDependencies('A');
    }

    public function testTopologicalOrderThrowsWhenGraphContainsCycles(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'A');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot produce a topological ordering');

        $graph->getTopologicalOrder();
    }

    public function testGetTransitiveDependenciesHandlesCyclicGraphWithoutInfiniteRecursion(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('A', 'B');
        $graph->addEdge('B', 'A');

        $transitive = $graph->getTransitiveDependencies('A');

        $this->assertContains('B', $transitive);
        $this->assertNotContains('A', $transitive);
    }

    public function testDefaultDepthGuardIsConfiguredAtFifty(): void
    {
        $graph = new DependencyGraph();

        for ($i = 0; $i < 49; $i++) {
            $graph->addEdge((string) $i, (string) ($i + 1));
        }

        $this->assertCount(50, $graph->getTopologicalOrder());
    }
}
