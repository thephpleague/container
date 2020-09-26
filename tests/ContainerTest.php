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
    /**
     * Asserts that the container can add and get a service.
     */
    public function testContainerAddsAndGets(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        self::assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    /**
     * Asserts that the container can add and get a service defined as shared.
     */
    public function testContainerAddsAndGetsShared(): void
    {
        $container = new Container();
        $container->share(Foo::class);
        self::assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        self::assertInstanceOf(Foo::class, $fooOne);
        self::assertInstanceOf(Foo::class, $fooTwo);
        self::assertSame($fooOne, $fooTwo);
    }

    /**
     * Asserts that the container can add and get a service defined as shared.
     */
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

    /**
     * Asserts that the container can add and get a service defined as non-shared with defaultToShared enabled.
     */
    public function testContainerAddsNonSharedWithSharedByDefault(): void
    {
        $container = (new Container())->defaultToShared();
        $container->add(Foo::class, null, false);
        self::assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        self::assertInstanceOf(Foo::class, $fooOne);
        self::assertInstanceOf(Foo::class, $fooTwo);
        self::assertNotSame($fooOne, $fooTwo);
    }

    /**
     * Asserts that the container can add and get services by tag.
     */
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

    /**
     * Asserts that the container can add and get a service from service provider.
     */
    public function testContainerAddsAndGetsWithServiceProvider(): void
    {
        $provider = new class extends AbstractServiceProvider
        {
            protected $provides = [
                Foo::class
            ];

            public function register()
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

    /**
     * Expect an exception to be thrown if a service provider lies
     * about providing a specific service.
     */
    public function testThrowsWhenServiceProviderLies(): void
    {
        $liar = new class extends AbstractServiceProvider
        {
            protected $provides = [
                'lie'
            ];

            public function register()
            {
            }
        };

        $container = new Container();

        $container->addServiceProvider($liar);
        self::assertTrue($container->has('lie'));

        $this->expectException(ContainerException::class);
        $container->get('lie');
    }

    /**
     * Asserts that the container can add and get a service from a delegate.
     */
    public function testContainerAddsAndGetsFromDelegate(): void
    {
        $delegate  = new ReflectionContainer();
        $container = new Container();
        $container->delegate($delegate);
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    /**
     * Asserts that the container throws an exception when cannot find service.
     */
    public function testContainerThrowsWhenCannotGetService(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        self::assertFalse($container->has(Foo::class));
        $container->get(Foo::class);
    }

    /**
     * Asserts that the container can find a definition to extend.
     */
    public function testContainerCanExtendDefinition(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        $definition = $container->extend(Foo::class);
        self::assertInstanceOf(DefinitionInterface::class, $definition);
    }

    /**
     * Asserts that the container can find a definition to extend from service provider.
     */
    public function testContainerCanExtendDefinitionFromServiceProvider(): void
    {
        $provider = new class extends AbstractServiceProvider
        {
            protected $provides = [
                Foo::class
            ];

            public function register()
            {
                $this->getContainer()->add(Foo::class);
            }
        };

        $container = new Container();
        $container->addServiceProvider($provider);
        $definition = $container->extend(Foo::class);
        self::assertInstanceOf(DefinitionInterface::class, $definition);
    }

    /**
     * Asserts that the container throws an exception when can't find definition to extend.
     */
    public function testContainerThrowsWhenCannotGetDefinitionToExtend(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        self::assertFalse($container->has(Foo::class));
        $container->extend(Foo::class);
    }

    /**
     * Asserts that the container adds and invokes an inflector.
     */
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
