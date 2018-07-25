---
layout: post
title: Getting Started
sections:
    What is container?: what-is-container
    Goals: goals
    Questions?: questions
    Installation: installation
---
[![Author](http://img.shields.io/badge/author-@philipobenito-blue.svg?style=flat-square)](https://twitter.com/philipobenito)
[![Latest Version](https://img.shields.io/github/release/thephpleague/container.svg?style=flat-square)](https://github.com/thephpleague/container/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/thephpleague/container/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/container)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/thephpleague/container.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/container/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/container.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/container)
[![Total Downloads](https://img.shields.io/packagist/dt/league/container.svg?style=flat-square)](https://packagist.org/packages/league/container)

## What is Container?

Container is a small but powerful dependency injection container that allows you to decouple components in your application in order to write clean and testable code.

The container can automatically resolve dependencies of objects resolved through it.

[Container on Packagist](https://packagist.org/packages/league/container)

## Goals

* Create a powerful dependency injection container that maintains speed and ease of use


## Questions?

Container was created by Phil Bennett. Find him on Twitter at [@philipobenito](https://twitter.com/philipobenito).

## Installation

### System Requirements

You need `PHP >= 5.4.0` to use `League\Container` but the latest stable version of PHP is recommended.

### Composer

Container is available on [Packagist](https://packagist.org/packages/league/container) and can be installed using [Composer](https://getcomposer.org/):

~~~
composer require league/container
~~~

Most modern frameworks will include Composer out of the box, but ensure the following file is included:

~~~ php
<?php

// include the Composer autoloader
require 'vendor/autoload.php';
~~~

## Going Solo

You can also use Container without using Composer by registering an autoloader function:

~~~ php
spl_autoload_register(function ($class) {
    $prefix = 'League\\Container\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
~~~

Or, use any other [PSR-4](http://www.php-fig.org/psr/psr-4/) compatible autoloader.
