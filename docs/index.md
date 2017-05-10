---
layout: homepage
releases:
  - name: League\Container 3.x
    type: next
    requires: PHP >= 7.0.0
    release: TBD
    support: TBD
    url: /3.x/
  - name: League\Container 2.x
    type: old
    requires: PHP >= 5.4.0
    release: 2.4.0 - 2017-03
    support: TBD
    url: /2.x/
  - name: League\Container 1.x
    type: legacy
    requires: PHP >= 5.4.0
    release: 1.3.2 - 2015-04
    support: 2015-10
    url: /1.x/
highlights:
  - Simple API
  - Interoperabiity. Container is an implementation of PSR-11.
  - Speed. Because Container is simple, it is also very fast.
  - Service Providers allow you to package code or configuration for packages that you reuse regularly.
  - Inflectors allow you to manipulate objects resolved through the container based on the type.
description: |
  Container is a simple but powerful dependency injection container that allows you to decouple components in your application in order to write clean and testable code.

  It is framework agnostic as well as being very fast because of it's simple API.
example: |
  ~~~php
  <?php

  $container = new League\Container\Container;

  // add a service to the container
  $container->add('service', 'Acme\Service\SomeService');

  // retrieve the service from the container
  $service = $container->get('service');

  var_dump($service instanceof Acme\Service\SomeService); // true
  ~~~
---
