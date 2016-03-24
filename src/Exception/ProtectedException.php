<?php

namespace League\Container\Exception;

use Interop\Container\Exception\ContainerException;
use RuntimeException;

class ProtectedException extends RuntimeException implements ContainerException
{
}
