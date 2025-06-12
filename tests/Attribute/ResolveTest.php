<?php

declare(strict_types=1);

namespace League\Container\Test\Attribute;

use League\Container\Attribute\Resolve;
use League\Container\Container;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Foo;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ResolveTest extends TestCase
{
    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCanInstantiateWithClassResolverAndSingleSegmentPath(): void
    {
        $foo = new Foo();
        $foo->setBar(new Bar());

        $container = $this->createMock(Container::class);
        $container->method('get')->with(Foo::class)->willReturn($foo);

        $resolve = new Resolve(Foo::class, 'bar');
        $resolve->setContainer($container);

        $this->assertInstanceOf(Resolve::class, $resolve);
        $this->assertObjectHasProperty('resolver', $resolve);
        $this->assertObjectHasProperty('path', $resolve);

        $this->assertInstanceOf(Bar::class, $resolve->resolve());
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCanInstantiateWithClassResolverAndMultiSegmentPath(): void
    {
        $foo = new Foo();
        $bar = new Bar();
        $bar->setSomething(['foo' => 'bar']);

        $foo->setBar($bar);

        $container = $this->createMock(Container::class);
        $container->method('get')->with(Foo::class)->willReturn($foo);

        $resolve = new Resolve(Foo::class, 'bar.getSomething.foo');
        $resolve->setContainer($container);

        $this->assertInstanceOf(Resolve::class, $resolve);
        $this->assertObjectHasProperty('resolver', $resolve);
        $this->assertObjectHasProperty('path', $resolve);

        $this->assertEquals('bar', $resolve->resolve());
    }
}
