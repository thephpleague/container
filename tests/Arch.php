<?php

declare(strict_types=1);

use League\Container\Compiler\CompilationException;
use League\Container\Exception\ContainerException;
use League\Container\Exception\NotFoundException;
use Psr\Container\NotFoundExceptionInterface;

arch('source files use strict types')
    ->expect('League\Container')
    ->toUseStrictTypes();

arch('compiler value objects are final')
    ->expect('League\Container\Compiler')
    ->classes()
    ->toBeFinal();

arch('compilation exception extends container exception')
    ->expect(CompilationException::class)
    ->toExtend(ContainerException::class);

arch('not found exception implements psr not found interface')
    ->expect(NotFoundException::class)
    ->toImplement(NotFoundExceptionInterface::class);
