<?php

declare(strict_types=1);

use League\Container\Attribute\Shared;

test('shared attribute targets classes only', function () {
    $reflection = new ReflectionClass(Shared::class);
    $attributes = $reflection->getAttributes(Attribute::class);

    expect($attributes)->toHaveCount(1);

    $attr = $attributes[0]->newInstance();
    expect($attr->flags)->toBe(Attribute::TARGET_CLASS);
});
