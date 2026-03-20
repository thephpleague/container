<?php

declare(strict_types=1);

uses()
    ->afterEach(function () {
        Mockery::close();
    })
    ->in(__DIR__);
