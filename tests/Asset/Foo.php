<?php

declare(strict_types=1);

namespace League\Container\Test\Asset;

class Foo
{
    public ?Bar $bar;

    public static ?Bar $staticBar;

    public static ?string $staticHello;

    public function __construct(?Bar $bar = null)
    {
        $this->bar = $bar;
    }

    public function setBar(Bar $bar): void
    {
        $this->bar = $bar;
    }

    public static function staticSetBar(Bar $bar, string $hello = 'hello world'): void
    {
        self::$staticHello = $hello;
        self::$staticBar = $bar;
    }
}
