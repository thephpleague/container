<?php

declare(strict_types=1);

namespace League\Container\Definition;

class Util
{
    public static function normalizeAlias(string $alias): string
    {
        if (strpos($alias, '\\') === 0) {
            return substr($alias, 1);
        }

        return $alias;
    }
}
