<?php

declare(strict_types=1);

namespace League\Container\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Shared {}
