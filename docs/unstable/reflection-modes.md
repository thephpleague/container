---
layout: post
title: Auto Wiring
sections:
    Introduction: introduction
    Usage: usage
---
## Introduction

The `ReflectionContainer` supports two modes, which can be enabled or disabled using the `mode` property:

- `ReflectionContainer::AUTO_WIRING` — Enables auto wiring (resolving dependencies by type-hint).
- `ReflectionContainer::ATTRIBUTE_RESOLUTION` — Enables attribute-based resolution (using attributes like `#[Inject]` or `#[Resolve]`).

## Usage

By default, both modes are enabled. You can control them like this:

~~~php
<?php 

use League\Container\ReflectionContainer;

// Enable only auto wiring (disable attribute resolution)
$container->delegate(
    new ReflectionContainer(
        cacheResolutions: false,
        mode: ReflectionContainer::AUTO_WIRING
    )
);

// Enable only attribute resolution (disable auto wiring)
$container->delegate(
    new ReflectionContainer(
        cacheResolutions: false,
        mode: ReflectionContainer::ATTRIBUTE_RESOLUTION
    )
);

// Enable both (default)
$container->delegate(
    new ReflectionContainer()
);

// Change mode at runtime
$reflectionContainer = new ReflectionContainer();
$reflectionContainer->setMode(ReflectionContainer::AUTO_WIRING);
~~~
