---
layout: post
title: Dependency Injection
sections:
    Constructor Injection: constructor-injection
    Setter Injection: setter-injection
    Factories: factories
---
Container at it's core is a simple dependency injection container, this section will focus on teaching you how to achieve different types of dependency injection using Container.

## Constructor Injection

Passing dependencies to a class constructor is the simplest way of achieving dependency injection. When your classes are defined with Container, it can easily wire them together for you using this method.

As a basic example, consider we have a controller class that depends on a model, and that model depends on PDO for database connections.

~~~php
<?php declare(strict_types=1);

namespace Acme;

class Controller
{
    /**
     * @var \Acme\Model
     */
    public $model;

    /**
     * Construct.
     *
     * @param \Acme\Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}

class Model
{
    /**
     * @var \PDO
     */
    public $pdo;

    /**
     * Construct.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
~~~

This dependency tree can be defined in Container, then whenever we retrieve `Acme\Controller`, Container will recursively build all dependencies and inject them as required.

~~~ php
<?php declare(strict_types=1);

$container = new League\Container\Container;

$container->add(Acme\Controller::class)->addArgument(Acme\Model::class);
$container->add(Acme\Model::class)->addArgument(PDO::class);

$container
    ->add(PDO::class)
    ->addArgument('dsn_string')
    ->addArgument('username')
    ->addArgument('password')
    ->addArgument([/* options */])
;

$controller = $container->get(Acme\Controller::class);

var_dump($controller instanceof Acme\Controller);   // true
var_dump($controller->model instanceof Acme\Model); // true
var_dump($controller->model->pdo instanceof PDO);   // true
~~~

## Setter Injection

Dependency injection can also be achieved by invoking and passing dependencies to setter methods. We can refactor the example above so that the model receives PDO via a setter instead of a constructor argument.

~~~php
<?php declare(strict_types=1);

namespace Acme;

class Controller
{
    /**
     * @var \Acme\Model
     */
    public $model;

    /**
     * Construct.
     *
     * @param \Acme\Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}

class Model
{
    /**
     * @var \PDO
     */
    public $pdo;

    /**
     * Set PDO.
     *
     * @param \PDO $pdo
     */
    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
~~~

Now we need to make a slight change to our definition in Container to ensure that the method is correctly invoked on instantiation.

~~~ php
<?php declare(strict_types=1);

$container = new League\Container\Container;

$container->add(Acme\Controller::class)->addArgument(Acme\Model::class);

$container
    ->add(Acme\Model::class)
    // first argument is the name of the method to invoke, the second argument
    // is an array of arguments to pass to the method, Container will attempt
    // to resolve each element of the array via itself
    ->addMethodCall('setPdo', [PDO::class])
;

$container
    ->add(PDO::class)
    ->addArgument('dsn_string')
    ->addArgument('username')
    ->addArgument('password')
    ->addArgument([/* options */])
;

$controller = $container->get(Acme\Controller::class);

var_dump($controller instanceof Acme\Controller);   // true
var_dump($controller->model instanceof Acme\Model); // true
var_dump($controller->model->pdo instanceof PDO);   // true
~~~

## Factories

Container can accept any `callable` that will be used as a factory to resolve your classes. This is the most performant way to resolve your objects as no inspection is needed of the definition, however, this does reduce the amount of flexibility you can take advantage of.

Using the same example as above, we can define it in Container as follows.

~~~ php
<?php declare(strict_types=1);

$container = new League\Container\Container;

$container->add(Acme\Controller::class, function () {
    $pdo   = new PDO('dsn_string', 'username', 'password', [/* options */]);
    $model = new Acme\Model;

    $model->setPdo($pdo);

    return new Acme\Controller($model);
});

$controller = $container->get(Acme\Controller::class);

var_dump($controller instanceof Acme\Controller);   // true
var_dump($controller->model instanceof Acme\Model); // true
var_dump($controller->model->pdo instanceof PDO);   // true
~~~
