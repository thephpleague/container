<?php

declare(strict_types=1);

namespace League\Container\Test\Attribute;

use League\Container\Attribute\Inject;
use League\Container\Container;
use League\Container\Test\Asset\Foo;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InjectTest extends TestCase
{
    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCanInstantiateWithId(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('get')->with(Foo::class)->willReturn(new Foo());

        $inject = new Inject(Foo::class);
        $inject->setContainer($container);

        $this->assertInstanceOf(Inject::class, $inject);
        $this->assertObjectHasProperty('id', $inject);

        $this->assertInstanceOf(Foo::class, $inject->resolve());
    }
}
