<?php

declare(strict_types=1);

use League\Container\Argument\ArgumentReflectorInterface;
use League\Container\Argument\ArgumentReflectorTrait;
use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\Container;
use League\Container\ContainerAwareTrait;
use League\Container\ReflectionContainer;
use League\Container\Test\Asset\Baz;
use Psr\Container\NotFoundExceptionInterface;

test('resolver resolves arguments via reflection', function () {
    $method = $this->getMockBuilder(ReflectionFunctionAbstract::class)->getMock();
    $param1 = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
    $param2 = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
    $param3 = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();
    $class = $this->getMockBuilder(ReflectionNamedType::class)->disableOriginalConstructor()->getMock();
    $container = $this->getMockBuilder(Container::class)->getMock();

    $class->expects($this->any())->method('getName')->willReturn('Class');
    $param1->expects($this->any())->method('getName')->willReturn('param1');
    $param1->expects($this->once())->method('getType')->willReturn($class);

    $param2->expects($this->any())->method('getName')->willReturn('param2');
    $param2->expects($this->once())->method('getType')->willReturn(null);
    $param2->expects($this->once())->method('isDefaultValueAvailable')->willReturn(true);
    $param2->expects($this->once())->method('getDefaultValue')->willReturn('value2');

    $param3->expects($this->any())->method('getName')->willReturn('param3');

    $method->expects($this->once())->method('getParameters')->willReturn([$param1, $param2, $param3]);

    $container->expects($this->once())->method('has')->with($this->equalTo('Class'))->willReturn(true);
    $container->expects($this->once())->method('get')->with($this->equalTo('Class'))->willReturn('classObject');

    $resolver = new class implements ArgumentReflectorInterface, ArgumentResolverInterface {
        use ArgumentReflectorTrait;
        use ArgumentResolverTrait;
        use ContainerAwareTrait;

        public function getMode(): int
        {
            return ReflectionContainer::ATTRIBUTE_RESOLUTION | ReflectionContainer::AUTO_WIRING;
        }
    };

    $resolver->setContainer($container);

    $args = $resolver->reflectArguments($method, ['param3' => 'value3']);

    expect($args[0])->toBe('classObject');
    expect($args[1])->toBe('value2');
    expect($args[2])->toBe('value3');
});

test('resolves default value argument', function () {
    $resolver = new class implements ArgumentReflectorInterface, ArgumentResolverInterface {
        use ArgumentReflectorTrait;
        use ArgumentResolverTrait;
        use ContainerAwareTrait;

        public function getMode(): int
        {
            return ReflectionContainer::ATTRIBUTE_RESOLUTION | ReflectionContainer::AUTO_WIRING;
        }
    };

    $result = $resolver->reflectArguments((new ReflectionClass(Baz::class))->getConstructor());

    expect($result)->toBe([null]);
});

test('resolver throws exception when reflection does not resolve', function () {
    $method = $this->getMockBuilder(ReflectionFunctionAbstract::class)->getMock();
    $param = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();

    $param->expects($this->once())->method('getName')->willReturn('param1');
    $param->expects($this->once())->method('getType')->willReturn(null);
    $param->expects($this->once())->method('isDefaultValueAvailable')->willReturn(false);

    $method->expects($this->once())->method('getParameters')->willReturn([$param]);

    $resolver = new class implements ArgumentReflectorInterface, ArgumentResolverInterface {
        use ArgumentReflectorTrait;
        use ArgumentResolverTrait;
        use ContainerAwareTrait;

        public function getMode(): int
        {
            return ReflectionContainer::ATTRIBUTE_RESOLUTION | ReflectionContainer::AUTO_WIRING;
        }
    };

    expect(fn () => $resolver->reflectArguments($method))->toThrow(NotFoundExceptionInterface::class);
});
