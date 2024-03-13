<?php

declare(strict_types=1);

namespace League\Container\Test;

use BadMethodCallException;
use League\Container\Definition\DefinitionInterface;
use League\Container\Exception\{ContainerException, NotFoundException};
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\Test\Asset\{Foo, Bar};
use League\Container\{Container, ContainerAwareTrait, ReflectionContainer};
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testContainerAddsAndGets(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        $this->assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerAddsAndGetsRecursively(): void
    {
        $container = new Container();
        $container->add(Bar::class, Foo::class);
        $container->add(Foo::class);
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Bar::class);
        $this->assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerAddsAndGetsShared(): void
    {
        $container = new Container();
        $container->addShared(Foo::class);
        $this->assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $fooOne);
        $this->assertInstanceOf(Foo::class, $fooTwo);
        $this->assertSame($fooOne, $fooTwo);
    }

    public function testContainerAddsAndGetsSharedByDefault(): void
    {
        $container = (new Container())->defaultToShared();
        $container->add(Foo::class);
        $this->assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $fooOne);
        $this->assertInstanceOf(Foo::class, $fooTwo);
        $this->assertSame($fooOne, $fooTwo);
    }

    public function testContainerAddsAndGetsFromTag(): void
    {
        $container = new Container();
        $container->add(Foo::class)->addTag('foobar');
        $container->add(Bar::class)->addTag('foobar');
        $this->assertTrue($container->has(Foo::class));

        $arrayOf = $container->get('foobar');

        $this->assertTrue($container->has('foobar'));
        $this->assertIsArray($arrayOf);
        $this->assertCount(2, $arrayOf);
        $this->assertInstanceOf(Foo::class, $arrayOf[0]);
        $this->assertInstanceOf(Bar::class, $arrayOf[1]);
    }

    public function testContainerAddsAndGetsNewFromTag(): void
    {
        $container = new Container();
        $container->add(Foo::class)->addTag('foobar');
        $container->add(Bar::class)->addTag('foobar');
        $this->assertTrue($container->has(Foo::class));

        $arrayOf = $container->get('foobar');

        $this->assertTrue($container->has('foobar'));
        $this->assertIsArray($arrayOf);
        $this->assertCount(2, $arrayOf);
        $this->assertInstanceOf(Foo::class, $arrayOf[0]);
        $this->assertInstanceOf(Bar::class, $arrayOf[1]);

        $arrayOfTwo = $container->getNew('foobar');
        $this->assertNotSame($arrayOfTwo, $arrayOf);
    }

    public function testContainerAddsAndGetsWithServiceProvider(): void
    {
        $provider = new class extends AbstractServiceProvider
        {
            public function provides(string $id): bool
            {
                return $id === Foo::class;
            }

            public function register(): void
            {
                $this->getContainer()->add(Foo::class);
            }
        };

        $container = new Container();

        $container->addServiceProvider($provider);
        $this->assertTrue($container->has(Foo::class));

        $foo = $container->get(Foo::class);
        $this->assertInstanceOf(Foo::class, $foo);
    }

    public function testThrowsWhenServiceProviderLies(): void
    {
        $liar = new class extends AbstractServiceProvider
        {
            public function provides(string $id): bool
            {
                return true;
            }

            public function register(): void
            {
            }
        };

        $container = new Container();

        $container->addServiceProvider($liar);
        $this->assertTrue($container->has('lie'));

        $this->expectException(ContainerException::class);
        $container->get('lie');
    }

    public function testContainerAddsAndGetsFromDelegate(): void
    {
        $delegate  = new ReflectionContainer();
        $container = new Container();
        $container->delegate($delegate);
        $foo = $container->get(Foo::class);
        $this->assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerThrowsWhenCannotGetService(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        $this->assertFalse($container->has(Foo::class));
        $container->get(Foo::class);
    }

    public function testContainerCanExtendDefinition(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        $definition = $container->extend(Foo::class);
        $this->assertSame(Foo::class, $definition->getAlias());
        $this->assertSame(Foo::class, $definition->getConcrete());
    }

    public function testContainerCanExtendDefinitionFromServiceProvider(): void
    {
        $provider = new class extends AbstractServiceProvider
        {
            public function provides(string $id): bool
            {
                return $id === Foo::class;
            }

            public function register(): void
            {
                $this->getContainer()->add(Foo::class);
            }
        };

        $container = new Container();
        $container->addServiceProvider($provider);
        $definition = $container->extend(Foo::class);
        $this->assertSame(Foo::class, $definition->getAlias());
        $this->assertSame(Foo::class, $definition->getConcrete());
    }

    public function testContainerThrowsWhenCannotGetDefinitionToExtend(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        $this->assertFalse($container->has(Foo::class));
        $container->extend(Foo::class);
    }

    public function testContainerAddsAndInvokesInflector(): void
    {
        $container = new Container();
        $container->inflector(Foo::class)->setProperty('bar', Bar::class);
        $container->add(Foo::class);
        $container->add(Bar::class);
        $foo = $container->get(Foo::class);
        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testContainerAwareCannotBeUsedWithoutImplementingInterface(): void
    {
        $this->expectException(BadMethodCallException::class);

        $class = new class {
            use ContainerAwareTrait;
        };

        $container = $this->getMockBuilder(Container::class)->getMock();
        $class->setContainer($container);
    }

    public function testNonExistentClassResolvesAsString(): void
    {
        $container = new Container();
        $container->add(NonExistent::class);

        $this->assertTrue($container->has(NonExistent::class));
        $this->assertSame(NonExistent::class, $container->get(NonExistent::class));
    }
}
