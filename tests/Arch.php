<?php

declare(strict_types=1);

arch('source files use strict types')
    ->expect('League\Container')
    ->toUseStrictTypes();

arch('compiler value objects are final')
    ->expect('League\Container\Compiler')
    ->classes()
    ->toBeFinal();

arch('compilation exception extends container exception')
    ->expect(League\Container\Compiler\CompilationException::class)
    ->toExtend(League\Container\Exception\ContainerException::class);

arch('not found exception implements psr not found interface')
    ->expect(League\Container\Exception\NotFoundException::class)
    ->toImplement(Psr\Container\NotFoundExceptionInterface::class);
