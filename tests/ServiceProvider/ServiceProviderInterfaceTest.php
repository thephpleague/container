<?php

namespace League\Container\Test\ServiceProvider;

use League\Container\ContainerAwareInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;

class ServiceProviderInterfaceTest extends \PHPUnit_Framework_TestCase
{
    public function testExtendsContainerAwareInterface()
    {
        $reflection = new \ReflectionClass(ServiceProviderInterface::class);

        $this->assertTrue($reflection->implementsInterface(ContainerAwareInterface::class));
    }
}
