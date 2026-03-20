<?php

declare(strict_types=1);

namespace League\Container\Compiler;

enum ConcreteType: string
{
    case ClassType = 'class';
    case Alias = 'alias';
    case Literal = 'literal';
    case StaticCallable = 'staticCallable';
    case InstanceCallable = 'instanceCallable';
    case Invokable = 'invokable';
}
