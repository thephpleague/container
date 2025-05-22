<?php

declare(strict_types=1);

namespace League\Container\Test;

use BadMethodCallException;
use League\Container\Exception\{ContainerException, NotFoundException};
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\Test\Asset\{Foo, Bar};
use League\Container\{Container, ContainerAwareTrait, ReflectionContainer};
use PHPUnit\Framework\TestCase;
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use stdClass;

class ContainerTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testContainerAddsAndGets(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        $this->assertInstanceOf(Foo::class, $foo);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testContainerAddsAndGetsRecursively(): void
    {
        $container = new Container();
        $container->add(Bar::class, Foo::class);
        $container->add(Foo::class);
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Bar::class);
        $this->assertInstanceOf(Foo::class, $foo);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testContainerAddsAndGetsSharedByDefault(): void
    {
        $container = new Container();
        $container->defaultToShared();
        $container->add(Foo::class);
        $this->assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $fooOne);
        $this->assertInstanceOf(Foo::class, $fooTwo);
        $this->assertSame($fooOne, $fooTwo);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testContainerAddsAndGetsFromDelegate(): void
    {
        $delegate  = new ReflectionContainer();
        $container = new Container();
        $container->delegate($delegate);
        $foo = $container->get(Foo::class);
        $this->assertInstanceOf(Foo::class, $foo);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testNonExistentClassResolvesAsString(): void
    {
        $container = new Container();
        $container->add('NonExistent');

        $this->assertTrue($container->has('NonExistent'));
        $this->assertSame('NonExistent', $container->get('NonExistent'));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testRuntimeOverwrite(): void
    {
        $concreteOne = new stdClass();
        $concreteTwo = new stdClass();

        $container = new Container();
        $container->add('foo', $concreteOne);
        $this->assertSame($concreteOne, $container->get('foo'));

        $container->add('foo', $concreteTwo, true);
        $this->assertSame($concreteTwo, $container->get('foo'));
        $this->assertNotSame($concreteOne, $container->get('foo'));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testDefaultOverwrite(): void
    {
        $concreteOne = new stdClass();
        $concreteTwo = new stdClass();

        $container = new Container();
        $container->defaultToOverwrite();
        $container->add('foo', $concreteOne);
        $this->assertSame($concreteOne, $container->get('foo'));

        $container->add('foo', $concreteTwo);
        $this->assertSame($concreteTwo, $container->get('foo'));
        $this->assertNotSame($concreteOne, $container->get('foo'));
    }
}
