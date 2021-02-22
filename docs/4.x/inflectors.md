---
layout: post
title: Inflectors
sections:
    Introduction: introduction
    Usage: usage
---
## Introduction

Inflectors allow you to define the manipulation of an object of a specific type as the final step before it is returned by the container.

This is useful for example when you want to invoke a method on all objects that implement a specific interface.

## Usage

Imagine that you have a `LoggerAwareInterface` and would like to invoke the method called `setLogger` passing in a logger every time a class is retrieved that implements this interface.

~~~ php
<?php 

declare(strict_types=1);

$container = new League\Container\Container();

$container->add(Acme\Logger::class);
$container->add(Acme\LoggerAwareClass::class); // implements LoggerAwareInterface
$container->add(Acme\Other\LoggerAwareClass::class); // implements LoggerAwareInterface

$container
    ->inflector(LoggerAwareInterface::class)
    ->invokeMethod('setLogger', [Acme\Logger::class]) // Acme\Logger will be resolved via the container
;
~~~

Now instead of adding a method call to each class individually we can simply define an inflector to invoke the method for every class of that type.
