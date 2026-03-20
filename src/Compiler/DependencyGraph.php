<?php

declare(strict_types=1);

namespace League\Container\Compiler;

use League\Container\Exception\ContainerException;

final class DependencyGraph
{
    private const int UNVISITED = 0;
    private const int IN_PROGRESS = 1;
    private const int COMPLETED = 2;

    /** @var array<string, list<string>> */
    private array $adjacency = [];

    public function __construct(
        private readonly int $depthGuard = 50,
    ) {}

    public function addNode(string $id): void
    {
        if (!array_key_exists($id, $this->adjacency)) {
            $this->adjacency[$id] = [];
        }
    }

    public function addEdge(string $from, string $to): void
    {
        $this->addNode($from);
        $this->addNode($to);

        if (!in_array($to, $this->adjacency[$from], strict: true)) {
            $this->adjacency[$from][] = $to;
        }
    }

    /**
     * @return list<list<string>>
     */
    public function detectCycles(): array
    {
        /** @var array<string, int> $colours */
        $colours = array_fill_keys(array_map('strval', array_keys($this->adjacency)), self::UNVISITED);
        /** @var list<string> $stack */
        $stack = [];
        /** @var list<list<string>> $cycles */
        $cycles = [];

        foreach (array_keys($this->adjacency) as $node) {
            $nodeId = (string) $node;
            if ($colours[$nodeId] === self::UNVISITED) {
                $this->depthFirstSearchForCycles($nodeId, $colours, $stack, $cycles, 0);
            }
        }

        return $cycles;
    }

    /**
     * @return list<string>
     */
    public function getTopologicalOrder(): array
    {
        $cycles = $this->detectCycles();

        if ($cycles !== []) {
            throw new ContainerException(
                'Cannot produce a topological ordering for a dependency graph that contains cycles.',
            );
        }

        /** @var array<string, int> $colours */
        $colours = array_fill_keys(array_map('strval', array_keys($this->adjacency)), self::UNVISITED);
        /** @var list<string> $order */
        $order = [];

        foreach (array_keys($this->adjacency) as $node) {
            $nodeId = (string) $node;
            if ($colours[$nodeId] === self::UNVISITED) {
                $this->depthFirstSearchForTopologicalOrder($nodeId, $colours, $order, 0);
            }
        }

        return array_reverse($order);
    }

    /**
     * @return list<string>
     */
    public function getDependencies(string $id): array
    {
        return $this->adjacency[$id] ?? [];
    }

    /**
     * @return list<string>
     */
    public function getTransitiveDependencies(string $id): array
    {
        $visited = [];

        $this->collectTransitiveDependencies($id, $visited, 0);

        return array_values(array_filter($visited, static fn(string $dep): bool => $dep !== $id));
    }

    /**
     * @param array<string, int> $colours
     * @param list<string> $stack
     * @param list<list<string>> $cycles
     */
    private function depthFirstSearchForCycles(
        string $node,
        array &$colours,
        array &$stack,
        array &$cycles,
        int $depth,
    ): void {
        $this->guardDepth($depth, $node);

        $colours[$node] = self::IN_PROGRESS;
        $stack[] = $node;

        foreach ($this->adjacency[$node] as $neighbour) {
            if ($colours[$neighbour] === self::IN_PROGRESS) {
                $cycleStart = array_search($neighbour, $stack, strict: true);
                $cyclePath = array_slice($stack, (int) $cycleStart);
                $cyclePath[] = $neighbour;
                $cycles[] = $cyclePath;
                continue;
            }

            if ($colours[$neighbour] === self::UNVISITED) {
                $this->depthFirstSearchForCycles($neighbour, $colours, $stack, $cycles, $depth + 1);
            }
        }

        array_pop($stack);
        $colours[$node] = self::COMPLETED;
    }

    /**
     * @param array<string, int> $colours
     * @param list<string> $order
     */
    private function depthFirstSearchForTopologicalOrder(
        string $node,
        array &$colours,
        array &$order,
        int $depth,
    ): void {
        $this->guardDepth($depth, $node);

        $colours[$node] = self::IN_PROGRESS;

        foreach ($this->adjacency[$node] as $neighbour) {
            if ($colours[$neighbour] === self::UNVISITED) {
                $this->depthFirstSearchForTopologicalOrder($neighbour, $colours, $order, $depth + 1);
            }
        }

        $colours[$node] = self::COMPLETED;
        $order[] = $node;
    }

    /** @param list<string> $visited */
    private function collectTransitiveDependencies(string $id, array &$visited, int $depth): void
    {
        $this->guardDepth($depth, $id);

        if (in_array($id, $visited, strict: true)) {
            return;
        }

        $visited[] = $id;

        foreach ($this->getDependencies($id) as $dependency) {
            $this->collectTransitiveDependencies($dependency, $visited, $depth + 1);
        }
    }

    private function guardDepth(int $depth, string $node): void
    {
        if ($depth >= $this->depthGuard) {
            throw new ContainerException(
                sprintf(
                    'Dependency graph depth guard of %d exceeded at node "%s". The dependency graph may contain an extremely deep or unbounded dependency chain.',
                    $this->depthGuard,
                    $node,
                ),
            );
        }
    }
}
