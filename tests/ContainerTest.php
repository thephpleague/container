<?php

namespace League\Container\Test;

use League\Container\Container;
use League\Container\Definition\Factory;
use League\Container\Test\Asset\Baz;
use League\Container\Test\Asset\BazStatic;
use League\Container\Test\Asset\Foo;

/**
 * ContainerTest
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    protected $configArray = [
        'League\Container\Test\Asset\Foo' => [
            'class' => 'League\Container\Test\Asset\Foo',
            'arguments' => ['League\Container\Test\Asset\Bar'],
            'methods' => [
                'injectBaz' => ['League\Container\Test\Asset\Baz']
            ]
        ],
        'League\Container\Test\Asset\Bar' => [
            'definition' => 'League\Container\Test\Asset\Bar',
            'arguments' => ['League\Container\Test\Asset\Baz']
        ],
        'League\Container\Test\Asset\Baz' => 'League\Container\Test\Asset\Baz',
    ];

    public function testAutoResolvesNestedDependenciesWithAliasedInterface()
    {
        $c = new Container;

        $c->add('League\Container\Test\Asset\BazInterface', 'League\Container\Test\Asset\Baz');

        $foo = $c->get('League\Container\Test\Asset\Foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->bar->baz);
        $this->assertInstanceOf('League\Container\Test\Asset\BazInterface', $foo->bar->baz);
    }

    public function testInjectsArgumentsAndInvokesMethods()
    {
        $c = new Container;

        $c->add('League\Container\Test\Asset\Bar')
          ->withArguments(['League\Container\Test\Asset\Baz']);

        $c->add('League\Container\Test\Asset\Baz');

        $c->add('League\Container\Test\Asset\Foo')
          ->withArgument('League\Container\Test\Asset\Bar')
          ->withMethodCall('injectBaz', ['League\Container\Test\Asset\Baz']);

        $foo = $c->get('League\Container\Test\Asset\Foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->baz);
    }

    public function testInjectsRuntimeArgumentsAndInvokesMethods()
    {
        $c = new Container;

        $c->add('League\Container\Test\Asset\Bar')
          ->withArguments(['League\Container\Test\Asset\Baz']);

        $c->add('closure1', function ($bar) use ($c) {
            return $c->get('League\Container\Test\Asset\Foo', [$bar]);
        })->withArgument('League\Container\Test\Asset\Bar');

        $c->add('League\Container\Test\Asset\Baz');

        $c->add('League\Container\Test\Asset\Foo')
          ->withArgument('League\Container\Test\Asset\Bar')
          ->withMethodCalls(['injectBaz' => ['League\Container\Test\Asset\Baz']]);

        $runtimeBar = new \League\Container\Test\Asset\Bar(
            new \League\Container\Test\Asset\Baz
        );

        $foo = $c->get('League\Container\Test\Asset\Foo', [$runtimeBar]);

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->baz);

        $this->assertSame($foo->bar, $runtimeBar);

        $fooClosure = $c->get('closure1');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $fooClosure);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $fooClosure->bar);
    }

    public function testSingletonReturnsSameInstanceEverytime()
    {
        $c = new Container;

        $c->singleton('League\Container\Test\Asset\Baz');

        $this->assertTrue($c->isSingleton('League\Container\Test\Asset\Baz'));

        $baz1 = $c->get('League\Container\Test\Asset\Baz');
        $baz2 = $c->get('League\Container\Test\Asset\Baz');

        $this->assertTrue($c->isSingleton('League\Container\Test\Asset\Baz'));
        $this->assertSame($baz1, $baz2);
    }

    public function testStoresAndInvokesClosure()
    {
        $c = new Container;

        $c->add('foo', function () {
            $foo = new \League\Container\Test\Asset\Foo(
                new \League\Container\Test\Asset\Bar(
                    new \League\Container\Test\Asset\Baz
                )
            );

            $foo->injectBaz(new \League\Container\Test\Asset\Baz);

            return $foo;
        });

        $foo = $c->get('foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->baz);
    }

    public function testStoresAndInvokesClosureWithDefinedArguments()
    {
        $c = new Container;

        $baz = new \League\Container\Test\Asset\Baz;
        $bar = new \League\Container\Test\Asset\Bar($baz);

        $c->add('foo', function ($bar, $baz) {
            $foo = new \League\Container\Test\Asset\Foo($bar);

            $foo->injectBaz($baz);

            return $foo;
        })->withArguments([$bar, $baz]);

        $foo = $c->get('foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->baz);
    }

    public function testStoresAndReturnsArbitraryValues()
    {
        $baz1 = new \League\Container\Test\Asset\Baz;
        $array1 = ['Phil', 'Bennett'];

        $c = new Container;

        $c->add('baz', $baz1);
        $baz2 = $c->get('baz');

        $c->add('array', $array1);
        $array2 = $c->get('array');

        $this->assertSame($baz1, $baz2);
        $this->assertSame($array1, $array2);
    }

    public function testReflectionOnNonClassThrowsException()
    {
        $this->setExpectedException('League\Container\Exception\ReflectionException');

        (new Container)->get('FakeClass');
    }

    public function testReflectionOnClassWithNoConstructorCreatesDefinition()
    {
        $c = new Container;

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $c->get('League\Container\Test\Asset\Baz'));
    }

    public function testReflectionInjectsDefaultValue()
    {
        $c = new Container;

        $this->assertSame('Phil Bennett', $c->get('League\Container\Test\Asset\FooWithDefaultArg')->name);
    }

    public function testReflectionThrowsExceptionForArgumentWithNoDefaultValue()
    {
        $this->setExpectedException('League\Container\Exception\UnresolvableDependencyException');

        $c = new Container;

        $c->get('League\Container\Test\Asset\FooWithNoDefaultArg');
    }

    public function testArrayAccessMapsToCorrectMethods()
    {
        $c = new Container;

        $c['League\Container\Test\Asset\Baz'] = 'League\Container\Test\Asset\Baz';

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $c['League\Container\Test\Asset\Baz']);

        $this->assertTrue(isset($c['League\Container\Test\Asset\Baz']));

        unset($c['League\Container\Test\Asset\Baz']);

        $this->assertFalse(isset($c['League\Container\Test\Asset\Baz']));
    }

    public function testContainerAcceptsArrayWithKey()
    {
        $c = new Container(['di' => $this->configArray]);

        $foo = $c->get('League\Container\Test\Asset\Foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->bar->baz);
        $this->assertInstanceOf('League\Container\Test\Asset\BazInterface', $foo->bar->baz);

        $baz = $c->get('League\Container\Test\Asset\Baz');
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->baz);
    }

    public function testContainerDoesntAcceptArrayWithoutKey()
    {
        $this->setExpectedException('RuntimeException');

        $c = new Container($this->configArray);
    }

    public function testContainerAcceptsArrayAccess()
    {
        $config = $this->getMock('ArrayAccess', ['offsetGet', 'offsetSet', 'offsetUnset', 'offsetExists']);
        $config->expects($this->any())
               ->method('offsetGet')
               ->with($this->equalTo('di'))
               ->will($this->returnValue($this->configArray));

        $config->expects($this->any())
               ->method('offsetExists')
               ->with($this->equalTo('di'))
               ->will($this->returnValue(true));


        $c = new Container($config);

        $foo = $c->get('League\Container\Test\Asset\Foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->bar->baz);
        $this->assertInstanceOf('League\Container\Test\Asset\BazInterface', $foo->bar->baz);

        $baz = $c->get('League\Container\Test\Asset\Baz');
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->baz);
    }

    public function testContainerDoesntAcceptInvalidConfigType()
    {
        $this->setExpectedException('InvalidArgumentException');

        $c = new Container(new \stdClass());
    }

    public function testExtendThrowsExceptionWhenUnregisteredServiceIsGiven()
    {
        $this->setExpectedException('InvalidArgumentException');

        $c = new Container;
        $c->extend('does_not_exist');
    }

    public function testExtendsThrowsExceptionWhenModifyingAnExistingSingleton()
    {
        $this->setExpectedException('League\Container\Exception\ServiceNotExtendableException');

        $c = new Container;
        $c->singleton('service', 'League\Container\Test\Asset\Baz');
        $c->get('service');
        $c->extend('service');
    }

    public function testExtendReturnsDefinitionForModificationWhenCalledWithAValidService()
    {
        $c = new Container;
        $definition = $c->add('service', 'League\Container\Test\Asset\Baz');
        $extend = $c->extend('service');

        $this->assertInstanceOf('League\Container\Definition\DefinitionInterface', $extend);
        $this->assertSame($definition, $extend);
    }

    public function testCallExecutesAnonymousFunction()
    {
        $expected = 'foo';

        $c = new Container();
        $result = $c->call(function () use ($expected) {
            return $expected;
        });

        $this->assertSame($result, $expected);
    }

    public function testCallExecutesNamedFunction()
    {
        $method = '\League\Container\Test\Asset\sayHi';

        $c = new Container;
        $returned = $c->call($method);
        $this->assertSame($returned, 'hi');
    }

    public function testCallExecutesCallableDefinedByArray()
    {
        $expected = 'qux';
        $baz = new BazStatic;

        $c = new Container;
        $returned = $c->call([$baz, 'qux']);

        $this->assertSame($returned, $expected);
    }

    public function testCallExecutesMethodsWithNamedParameters()
    {
        $expected = 'bar';

        $c = new Container;
        $returned = $c->call(function ($foo) {
            return $foo;
        }, ['foo' => $expected]);

        $this->assertSame($returned, $expected);
    }

    public function testCallExecutesStaticMethod()
    {
        $method = '\League\Container\Test\Asset\BazStatic::baz';
        $expected = 'qux';

        $c = new Container;
        $returned = $c->call($method, ['foo' => $expected]);
        $this->assertSame($returned, $expected);
    }

    public function testCallResolvesTypeHintedArgument()
    {
        $expected = 'League\Container\Test\Asset\Baz';

        $c = new Container;
        $returned = $c->call(function (Baz $baz) use ($expected) {
            return get_class($baz);
        });

        $this->assertSame($returned, $expected);
    }

    public function testCallMergesTypeHintedAndProvidedAttributes()
    {
        $expected = 'bar+League\Container\Test\Asset\Baz';

        $c = new Container;
        $returned = $c->call(function ($foo, Baz $baz) use ($expected) {
            return $foo.'+'.get_class($baz);
        }, ['foo' => 'bar']);

        $this->assertSame($returned, $expected);
    }

    public function testCallFillsInDefaultParameterValues()
    {
        $expected = 'bar';

        $c = new Container;
        $returned = $c->call(function ($foo = 'bar') {
            return $foo;
        });

        $this->assertSame($returned, $expected);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCallThrowsRuntimeExceptionIfParameterResolutionFails()
    {
        $c = new Container;
        $c->call(function (array $foo) {
            return implode(',', $foo);
        });

        $this->assertFalse(true);
    }

    public function testCallDoesntThinksArrayTypeHintAreToBeResolvedByContainer()
    {
        $c = new Container();
        $returned = $c->call(function (array $foo = []) {
            return $foo;
        });

        $this->assertInternalType('array', $returned);
        $this->assertEmpty($returned);
    }

    public function testContainerResolvesRegisteredCallable()
    {
        $c = new Container;

        $c->add('League\Container\Test\Asset\BazInterface', 'League\Container\Test\Asset\Baz');

        $c->invokable('function', function (\League\Container\Test\Asset\Foo $foo) {
            return $foo;
        })->withArgument('League\Container\Test\Asset\Foo');

        $foo = $c->call('function');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->bar->baz);
        $this->assertInstanceOf('League\Container\Test\Asset\BazInterface', $foo->bar->baz);
    }

    public function testCallThrowsExceptionWhenCannotResolveCallable()
    {
        $this->setExpectedException('RuntimeException');

        $c = new Container;

        $c->call('hello');
    }
}
