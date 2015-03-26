<?php

namespace League\Container\Test;

use League\Container\Container;
use League\Container\Definition\Factory;
use League\Container\Test\Asset\Bar;
use League\Container\Test\Asset\Baz;
use League\Container\Test\Asset\BazStatic;
use League\Container\Test\Asset\Foo;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
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

    /**
     * Asserts that an infector is applied when resolving a class
     *
     * @return void
     */
    public function testInflectorIsAppliedAfterResolution()
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

        $c->inflector('League\Container\Test\Asset\Foo')
          ->setProperties([
              'bar' => 'League\Container\Test\Asset\Baz',
              'baz' => 'League\Container\Test\Asset\Bar'
          ]);

        $foo = $c->get('League\Container\Test\Asset\Foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->baz);

        $c->inflector('League\Container\Test\Asset\Foo')
          ->invokeMethods([
              'injectBaz' => ['League\Container\Test\Asset\Baz']
          ]);

        $foo = $c->get('League\Container\Test\Asset\Foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->baz);

        $c->inflector('League\Container\Test\Asset\Foo', function ($foo) use ($c) {
            $foo->bar = $c->get('League\Container\Test\Asset\Baz');
        });

        $foo = $c->get('League\Container\Test\Asset\Foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->bar);
    }

    /**
     * Asserts that a service provider can be registered and service resolved
     * via it
     *
     * @return void
     */
    public function testContainerAddAcceptsServiceProvider()
    {
        $c = new Container;

        $c->add((new Asset\ServiceProviderFake));

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $c->get('test'));
        $this->assertInstanceOf('stdClass', $c->get('test.instance'));
    }

    /**
     * Asserts that service provider can be registered by string reference with alias
     *
     * @return void
     */
    public function testContainerAddServiceProviderAcceptsServiceProviderByReference()
    {
        $c = new Container;

        $c->addServiceProvider('League\Container\Test\Asset\ServiceProviderFake');

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $c->get('test'));
        $this->assertInstanceOf('stdClass', $c->get('test.instance'));
    }

    /**
     * Asserts that the service providers registers a scalar value.
     *
     * @return void
     */
    public function testArbitraryValuesAreRegisteredInServiceProvider()
    {
        $c = new Container;

        $c->addServiceProvider('League\Container\Test\Asset\ServiceProviderFake');

        $this->assertEquals('value', $c->get('test.variable'));
    }

    /**
     * Asserts that an exteption is thrown when attempting to register an invalid
     * type as a service provider.
     *
     * @return void
     */
    public function testExceptionIsThrownWhenRegisteringServiceProviderWithInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException');

        $c = new Container;

        $c->addServiceProvider(new \stdClass);
    }

    /**
     * Asserts that container auto resolves dependencies with defined interface
     * alias
     *
     * @return void
     */
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

    /**
     * Asserts that container injects arguments and invokes methods on
     * definition
     *
     * @return void
     */
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

    /**
     * Asserts that container favours runtime arguments and invokes methods on
     * definition
     *
     * @return void
     */
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

    /**
     * Asserts that container returns same instance every call when registered
     * as singleton
     *
     * @return void
     */
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

    /**
     * Asserts that container stores and invokes closure
     *
     * @return void
     */
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

    /**
     * Asserts that container invokes closure with defined arguments
     *
     * @return void
     */
    public function testStoresAndInvokesClosureWithDefinedArguments()
    {
        $c = new Container;

        $baz = new \League\Container\Test\Asset\Baz;
        $bar = new \League\Container\Test\Asset\Bar($baz);

        $c->add('foo', function (Bar $bar, Baz $baz) {
            $foo = new \League\Container\Test\Asset\Foo($bar);

            $foo->injectBaz($baz);

            return $foo;
        })->withArguments([$bar, $baz]);

        $foo = $c->get('foo');

        $this->assertInstanceOf('League\Container\Test\Asset\Foo', $foo);
        $this->assertInstanceOf('League\Container\Test\Asset\Bar', $foo->bar);
        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $foo->baz);
    }

    /**
     * Asserts that container stores and returns arbitrary types
     *
     * @return void
     */
    public function testStoresAndReturnsArbitraryTypes()
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

    /**
     * Asserts that reflection on non class throws exception
     *
     * @return void
     */
    public function testReflectionOnNonClassThrowsException()
    {
        $this->setExpectedException('League\Container\Exception\ReflectionException');

        (new Container)->get('FakeClass');
    }

    /**
     * Asserts that reflecting on class with no constructor creates a definition
     *
     * @return void
     */
    public function testReflectionOnClassWithNoConstructorCreatesDefinition()
    {
        $c = new Container;

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $c->get('League\Container\Test\Asset\Baz'));
    }

    /**
     * Asserts that reflection injects a default value when available
     *
     * @return void
     */
    public function testReflectionInjectsDefaultValue()
    {
        $c = new Container;

        $this->assertSame('Phil Bennett', $c->get('League\Container\Test\Asset\FooWithDefaultArg')->name);
    }

    /**
     * Asserts that an exception is thrown when trying to reflect on argument
     * with no default value
     *
     * @return void
     */
    public function testReflectionThrowsExceptionForArgumentWithNoDefaultValue()
    {
        $this->setExpectedException('League\Container\Exception\UnresolvableDependencyException');

        $c = new Container;

        $c->get('League\Container\Test\Asset\FooWithNoDefaultArg');
    }

    /**
     * Asserts that array access is mapped to correct methods
     *
     * @return void
     */
    public function testArrayAccessMapsToCorrectMethods()
    {
        $c = new Container;

        $c['League\Container\Test\Asset\Baz'] = 'League\Container\Test\Asset\Baz';

        $this->assertInstanceOf('League\Container\Test\Asset\Baz', $c['League\Container\Test\Asset\Baz']);

        $this->assertTrue(isset($c['League\Container\Test\Asset\Baz']));

        unset($c['League\Container\Test\Asset\Baz']);

        $this->assertFalse(isset($c['League\Container\Test\Asset\Baz']));
    }

    /**
     * Asserts that config is accepted with correct key
     *
     * @return void
     */
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

    /**
     * Asserts that an exception is thrown when config key is absent
     *
     * @return void
     */
    public function testContainerDoesntAcceptArrayWithoutKey()
    {
        $this->setExpectedException('RuntimeException');

        $c = new Container($this->configArray);
    }

    /**
     * Asserts that container accepts instance of \ArrayAccess as config
     *
     * @return void
     */
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

    /**
     * Asserts that container rejects invalid config types
     *
     * @return void
     */
    public function testContainerDoesntAcceptInvalidConfigType()
    {
        $this->setExpectedException('InvalidArgumentException');

        $c = new Container(new \stdClass());
    }

    /**
     * Asserts that exception is thrown when attempting to extend unregistered
     * definition
     *
     * @return void
     */
    public function testExtendThrowsExceptionWhenUnregisteredDefinitionIsGiven()
    {
        $this->setExpectedException('InvalidArgumentException');

        $c = new Container;
        $c->extend('does_not_exist');
    }

    /**
     * Asserts that exception is thrown when trying to extend definition being
     * managed as a singleton
     *
     * @return void
     */
    public function testExtendsThrowsExceptionWhenModifyingAnExistingSingleton()
    {
        $this->setExpectedException('League\Container\Exception\ServiceNotExtendableException');

        $c = new Container;
        $c->singleton('service', 'League\Container\Test\Asset\Baz');
        $c->get('service');
        $c->extend('service');
    }

    /**
     * Asserts that definition is returned for extending
     *
     * @return void
     */
    public function testExtendReturnsDefinitionForModificationWhenCalledWithAValidService()
    {
        $c = new Container;
        $definition = $c->add('service', 'League\Container\Test\Asset\Baz');
        $extend = $c->extend('service');

        $this->assertInstanceOf('League\Container\Definition\DefinitionInterface', $extend);
        $this->assertSame($definition, $extend);
    }

    /**
     * Asserts that the alias is checked in service providers as well
     *
     * @return void
     */
    public function testExtendLooksForAliasInServiceProviders()
    {
        $c = new Container;
        $c->addServiceProvider('League\Container\Test\Asset\ServiceProviderFake');
        $c->extend('test');
    }

    /**
     * Asserts that call invokes closure
     *
     * @return void
     */
    public function testCallInvokesClosure()
    {
        $expected = 'foo';

        $c = new Container();
        $result = $c->call(function () use ($expected) {
            return $expected;
        });

        $this->assertSame($result, $expected);
    }

    /**
     * Asserts that call invokes named function
     *
     * @return void
     */
    public function testCallInvokesNamedFunction()
    {
        $method = '\League\Container\Test\Asset\sayHi';

        $c = new Container;
        $returned = $c->call($method);
        $this->assertSame($returned, 'hi');
    }

    /**
     * Asserts that call accepts array based callable
     *
     * @return void
     */
    public function testCallInvokesCallableDefinedByArray()
    {
        $expected = 'qux';
        $baz = new BazStatic;

        $c = new Container;
        $returned = $c->call([$baz, 'qux']);

        $this->assertSame($returned, $expected);
    }

    /**
     * Asserts that call invokes callable with named arguments
     *
     * @return void
     */
    public function testCallInvokesMethodsWithNamedArguments()
    {
        $expected = 'bar';

        $c = new Container;
        $returned = $c->call(function ($foo) {
            return $foo;
        }, ['foo' => $expected]);

        $this->assertSame($returned, $expected);
    }

    /**
     * Asserts that call invokes a static method
     *
     * @return void
     */
    public function testCallInvokesStaticMethod()
    {
        $method = '\League\Container\Test\Asset\BazStatic::baz';
        $expected = 'qux';

        $c = new Container;
        $returned = $c->call($method, ['foo' => $expected]);
        $this->assertSame($returned, $expected);
    }

    /**
     * Asserts that call auto resolves a type hinted argument
     *
     * @return void
     */
    public function testCallResolvesTypeHintedArgument()
    {
        $expected = 'League\Container\Test\Asset\Baz';

        $c = new Container;
        $returned = $c->call(function (Baz $baz) use ($expected) {
            return get_class($baz);
        });

        $this->assertSame($returned, $expected);
    }

    /**
     * Asserts that call merges resolved and provided arguments
     *
     * @return void
     */
    public function testCallMergesTypeHintedAndProvidedArguments()
    {
        $expected = 'bar+League\Container\Test\Asset\Baz';

        $c = new Container;
        $returned = $c->call(function ($foo, Baz $baz) {
            return $foo . '+' . get_class($baz);
        }, ['foo' => 'bar']);

        $this->assertSame($returned, $expected);
    }

    /**
     * Asserts call resolves arguments with default value
     *
     * @return void
     */
    public function testCallResolvesDefaultArgumentValues()
    {
        $expected = 'bar';

        $c = new Container;
        $returned = $c->call(function ($foo = 'bar') {
            return $foo;
        });

        $this->assertSame($returned, $expected);
    }

    /**
     * Asserts that exception is thrown if auto resolution of arguments fails
     *
     * @return void
     */
    public function testCallThrowsRuntimeExceptionIfArgumentResolutionFails()
    {
        $this->setExpectedException('RuntimeException');

        $c = new Container;
        $c->call(function (array $foo) {
            return implode(',', $foo);
        });

        $this->assertFalse(true);
    }

    /**
     * Asserts that array type hint is ignore when auto resolving arguments
     *
     * @return void
     */
    public function testCallIgnoresArrayTypeHint()
    {
        $c = new Container;
        $returned = $c->call(function (array $foo = []) {
            return $foo;
        });

        $this->assertInternalType('array', $returned);
        $this->assertEmpty($returned);
    }

    /**
     * Assert container resolves a registered closure via call
     *
     * @return void
     */
    public function testContainerResolvesRegisteredClosure()
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

    /**
     * Assert container resolves a registered named function via call
     *
     * @return void
     */
    public function testContainerResolvesRegisteredNamedFunction()
    {
        $c = new Container;

        $c->invokable('function', '\League\Container\Test\Asset\withArgument')->withArgument('hello');

        $this->assertSame($c->call('function'), 'hello');
    }

    /**
     * Asserts that a class method is registered as a callable when aliased as string
     *
     * @return void
     */
    public function testContainerResolvesRegisteredClassMethodWhenRegisteredAsString()
    {
        $c = new Container;

        $c->invokable('League\Container\Test\Asset\BazStatic::qux');

        $this->assertSame($c->call('League\Container\Test\Asset\BazStatic::qux'), 'qux');
    }

    /**
     * Asserts that an exception is thrown when attempting to register a non
     * callable
     *
     * @return void
     */
    public function testExceptionThrownWhenRegisteringNonCallable()
    {
        $this->setExpectedException('InvalidArgumentException');

        $c = new Container;

        $c->invokable('League\Container\Test\Asset\BazStatic');
    }

    /**
     * Assert exception is thrown when callable cannot be resolved
     *
     * @return void
     */
    public function testCallThrowsExceptionWhenCannotResolveCallable()
    {
        $this->setExpectedException('RuntimeException');

        $c = new Container;

        $c->call('hello');
    }

    /**
     * https://github.com/thephpleague/container/issues/10
     *
     * @group regression
     */
    public function testValuesInConfigurationShouldBeRetreivableFromContainerAfterInstantiation()
    {
        $c = new Container([
            'di'    => [
                'a_key' => 123,
            ],
        ]);

        $this->assertEquals(123, $c->get('a_key'));
    }

    public function testArgsArePassedToNewlyReflectedClasses()
    {
        $expected = 'Jimmy Puckett';

        $c = new Container();

        $f = $c->get('League\Container\Test\Asset\FooWithDefaultArg', [$expected]);

        $this->assertEquals($expected, $f->name);
    }
}
