<?php

declare(strict_types=1);

namespace League\Container\Test\Compiler;

use League\Container\Compiler\CompiledDefinition;
use League\Container\Compiler\ConcreteType;
use PHPUnit\Framework\TestCase;

class CompiledDefinitionTest extends TestCase
{
    public function testConstructorStoresAllProperties(): void
    {
        $definition = new CompiledDefinition(
            id: 'App\Service',
            concreteType: ConcreteType::ClassType,
            shared: true,
            resolvedArguments: ['new App\Dependency()'],
            methodCalls: [['method' => 'setLogger', 'arguments' => ['$logger']]],
            tags: ['service', 'loggable'],
            concreteClass: 'App\Service',
            factoryClass: null,
            factoryMethod: null,
        );

        $this->assertSame('App\Service', $definition->id);
        $this->assertSame(ConcreteType::ClassType, $definition->concreteType);
        $this->assertTrue($definition->shared);
        $this->assertSame(['new App\Dependency()'], $definition->resolvedArguments);
        $this->assertSame([['method' => 'setLogger', 'arguments' => ['$logger']]], $definition->methodCalls);
        $this->assertSame(['service', 'loggable'], $definition->tags);
        $this->assertSame('App\Service', $definition->concreteClass);
        $this->assertNull($definition->factoryClass);
        $this->assertNull($definition->factoryMethod);
    }

    public function testFactoryDefinitionStoresFactoryDetails(): void
    {
        $definition = new CompiledDefinition(
            id: 'App\Service',
            concreteType: ConcreteType::StaticCallable,
            shared: false,
            resolvedArguments: [],
            methodCalls: [],
            tags: [],
            concreteClass: null,
            factoryClass: 'App\ServiceFactory',
            factoryMethod: 'create',
        );

        $this->assertSame(ConcreteType::StaticCallable, $definition->concreteType);
        $this->assertFalse($definition->shared);
        $this->assertNull($definition->concreteClass);
        $this->assertSame('App\ServiceFactory', $definition->factoryClass);
        $this->assertSame('create', $definition->factoryMethod);
    }

    public function testPropertiesAreReadonly(): void
    {
        $definition = new CompiledDefinition(
            id: 'App\Service',
            concreteType: ConcreteType::ClassType,
            shared: false,
            resolvedArguments: [],
            methodCalls: [],
            tags: [],
            concreteClass: 'App\Service',
            factoryClass: null,
            factoryMethod: null,
        );

        $this->expectException(\Error::class);
        $definition->id = 'mutated';
    }
}
