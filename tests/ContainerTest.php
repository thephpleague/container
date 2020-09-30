<?php

declare(strict_types=1);

namespace League\Container\Test;

use League\Container\Definition\DefinitionInterface;
use League\Container\Exception\{ContainerException, NotFoundException};
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\Test\Asset\{Foo, Bar};
use League\Container\{Container, ReflectionContainer};
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testContainerAddsAndGets(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        self::assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerAddsAndGetsRecursively(): void
    {
        $container = new Container();
        $container->add(Bar::class, Foo::class);
        $container->add(Foo::class);
        self::assertTrue($container->has(Foo::class));
        $foo = $container->get(Bar::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerAddsAndGetsShared(): void
    {
        $container = new Container();
        $container->addShared(Foo::class);
        self::assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        self::assertInstanceOf(Foo::class, $fooOne);
        self::assertInstanceOf(Foo::class, $fooTwo);
        self::assertSame($fooOne, $fooTwo);
    }

    public function testContainerAddsAndGetsSharedByDefault(): void
    {
        $container = (new Container())->defaultToShared();
        $container->add(Foo::class);
        self::assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        self::assertInstanceOf(Foo::class, $fooOne);
        self::assertInstanceOf(Foo::class, $fooTwo);
        self::assertSame($fooOne, $fooTwo);
    }

    public function testContainerAddsAndGetsFromTag(): void
    {
        $container = new Container();
        $container->add(Foo::class)->addTag('foobar');
        $container->add(Bar::class)->addTag('foobar');
        self::assertTrue($container->has(Foo::class));

        $arrayOf = $container->get('foobar');

        self::assertTrue($container->has('foobar'));
        self::assertIsArray($arrayOf);
        self::assertCount(2, $arrayOf);
        self::assertInstanceOf(Foo::class, $arrayOf[0]);
        self::assertInstanceOf(Bar::class, $arrayOf[1]);
    }

    public function testContainerAddsAndGetsWithServiceProvider(): void
    {
        $provider = new class extends AbstractServiceProvider
        {
            protected $provides = [
                Foo::class
            ];

            public function register(): void
            {
                $this->getContainer()->add(Foo::class);
            }
        };

        $container = new Container();

        $container->addServiceProvider($provider);
        self::assertTrue($container->has(Foo::class));

        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    public function testThrowsWhenServiceProviderLies(): void
    {
        $liar = new class extends AbstractServiceProvider
        {
            protected $provides = [
                'lie'
            ];

            public function register(): void
            {
            }
        };

        $container = new Container();

        $container->addServiceProvider($liar);
        self::assertTrue($container->has('lie'));

        $this->expectException(ContainerException::class);
        $container->get('lie');
    }

    public function testContainerAddsAndGetsFromDelegate(): void
    {
        $delegate  = new ReflectionContainer();
        $container = new Container();
        $container->delegate($delegate);
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerThrowsWhenCannotGetService(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        self::assertFalse($container->has(Foo::class));
        $container->get(Foo::class);
    }

    public function testContainerCanExtendDefinition(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        $definition = $container->extend(Foo::class);
        self::assertSame(Foo::class, $definition->getAlias());
        self::assertSame(Foo::class, $definition->getConcrete());
    }

    public function testContainerCanExtendDefinitionFromServiceProvider(): void
    {
        $provider = new class extends AbstractServiceProvider
        {
            protected $provides = [
                Foo::class
            ];

            public function register(): void
            {
                $this->getContainer()->add(Foo::class);
            }
        };

        $container = new Container();
        $container->addServiceProvider($provider);
        $definition = $container->extend(Foo::class);
        self::assertSame(Foo::class, $definition->getAlias());
        self::assertSame(Foo::class, $definition->getConcrete());
    }

    public function testContainerThrowsWhenCannotGetDefinitionToExtend(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        self::assertFalse($container->has(Foo::class));
        $container->extend(Foo::class);
    }

    public function testContainerAddsAndInvokesInflector(): void
    {
        $container = new Container();
        $container->inflector(Foo::class)->setProperty('bar', Bar::class);
        $container->add(Foo::class);
        $container->add(Bar::class);
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }
}
