<?php

namespace League\Container\Test\Asset;

class Foo
{
    public $bar;

    public static $staticBar;

    public static $staticHello;

    public function __construct(Bar $bar = null)
    {
        $this->bar = $bar;
    }

    public function setBar(Bar $bar)
    {
        $this->bar = $bar;
    }

    public static function staticSetBar(Bar $bar, $hello = 'hello world')
    {
        self::$staticHello = $hello;
        self::$staticBar = $bar;
    }
}
