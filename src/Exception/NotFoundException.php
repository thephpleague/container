<?php

declare(strict_types=1);

namespace League\Container\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface {}
