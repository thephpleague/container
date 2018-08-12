<?php declare(strict_types=1);

namespace League\Container\Test;

use League\Container\Definition\DefinitionInterface;
use League\Container\Exception\NotFoundException;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\Test\Asset\{Foo, Bar};
use League\Container\{Container, ReflectionContainer};
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * Asserts that the container can add and get a service.
     */
    public function testContainerAddsAndGets()
    {
        $container = new Container;

        $container->add(Foo::class);

        $this->assertTrue($container->has(Foo::class));

        $foo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $foo);
    }

    /**
     * Asserts that the container can add and get a service defined as shared.
     */
    public function testContainerAddsAndGetsShared()
    {
        $container = new Container;

        $container->share(Foo::class);

        $this->assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $fooOne);
        $this->assertInstanceOf(Foo::class, $fooTwo);
        $this->assertSame($fooOne, $fooTwo);
    }

    /**
     * Asserts that the container can add and get a service defined as shared.
     */
    public function testContainerAddsAndGetsSharedByDefault()
    {
        $container = (new Container)->defaultToShared();

        $container->share(Foo::class);

        $this->assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $fooOne);
        $this->assertInstanceOf(Foo::class, $fooTwo);
        $this->assertSame($fooOne, $fooTwo);
    }

    /**
     * Asserts that the container can add and get services by tag.
     */
    public function testContainerAddsAndGetsFromTag()
    {
        $container = new Container;

        $container->add(Foo::class)->addTag('foobar');
        $container->add(Bar::class)->addTag('foobar');

        $this->assertTrue($container->has(Foo::class));

        $arrayOf = $container->get('foobar');

        $this->assertTrue($container->has('foobar'));
        $this->assertInternalType('array', $arrayOf);
        $this->assertCount(2, $arrayOf);
        $this->assertInstanceOf(Foo::class, $arrayOf[0]);
        $this->assertInstanceOf(Bar::class, $arrayOf[1]);
    }

    /**
     * Asserts that the container can add and get a service from service provider.
     */
    public function testContainerAddsAndGetsWithServiceProvider()
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

        $container = new Container;

        $container->addServiceProvider($provider);

        $this->assertTrue($container->has(Foo::class));

        $foo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $foo);
    }

    /**
     * Asserts that the container can add and get a service from a delegate.
     */
    public function testContainerAddsAndGetsFromDelegate()
    {
        $delegate  = new ReflectionContainer;
        $container = new Container;

        $container->delegate($delegate);

        $foo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $foo);
    }

    /**
     * Asserts that the container throws an exception when cannot find service.
     */
    public function testContainerThrowsWhenCannotGetService()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container;

        $this->assertFalse($container->has(Foo::class));

        $container->get(Foo::class);
    }

    /**
     * Asserts that the container can find a definition to extend.
     */
    public function testContainerCanExtendDefinition()
    {
        $container = new Container;

        $container->add(Foo::class);

        $definition = $container->extend(Foo::class);

        $this->assertInstanceOf(DefinitionInterface::class, $definition);
    }

    /**
     * Asserts that the container can find a definition to extend from service provider.
     */
    public function testContainerCanExtendDefinitionFromServiceProvider()
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

        $container = new Container;

        $container->addServiceProvider($provider);

        $definition = $container->extend(Foo::class);

        $this->assertInstanceOf(DefinitionInterface::class, $definition);
    }

    /**
     * Asserts that the container throws an exception when can't find definition to extend.
     */
    public function testContainerThrowsWhenCannotGetDefinitionToExtend()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container;

        $this->assertFalse($container->has(Foo::class));

        $container->extend(Foo::class);
    }

    /**
     * Asserts that the container adds and invokes an inflector.
     */
    public function testContainerAddsAndInvokesInflector()
    {
        $container = new Container;

        $container->inflector(Foo::class)->setProperty('bar', Bar::class);

        $container->add(Foo::class);
        $container->add(Bar::class);

        $foo = $container->get(Foo::class);

        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }
}
