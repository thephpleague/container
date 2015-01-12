# Dic (Dependency Injection Container)

[![Author](http://img.shields.io/badge/author-@philipobenito-blue.svg?style=flat-square)](https://twitter.com/philipobenito)
[![Latest Version](https://img.shields.io/github/release/thephpleague/dic.svg?style=flat-square)](https://github.com/thephpleague/dic/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/thephpleague/dic/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/dic)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/thephpleague/dic.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/dic/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/dic.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/dic)
[![Total Downloads](https://img.shields.io/packagist/dt/league/dic.svg?style=flat-square)](https://packagist.org/packages/league/dic)

This package is compliant with [PSR-1], [PSR-2] and [PSR-4]. If you notice compliance oversights,
please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

## Install

Via Composer

``` bash
$ composer require league/dic
```

## Requirements

The following versions of PHP are supported by this version.

* PHP 5.4
* PHP 5.5
* PHP 5.6
* HHVM

## Documentation

Dic has [full documentation](http://dic.thephpleague.com), powered by [Jekyll](http://jekyllrb.com/).

Contribute to this documentation in the [gh-pages branch](https://github.com/thephpleague/dic/tree/gh-pages/).

## Todo

- Implement League version of Di when migrated from Orno.
- Add knowledge of variadic uri wildcard arguments to `MethodArgumentStrategy`. (Blocked by changes to Di).

## Testing

``` bash
$ vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/thephpleague/dic/blob/master/CONTRIBUTING.md) for details.

## Credits

- [Nikita Popov](https://github.com/nikic)
- [Phil Bennett](https://github.com/philipobenito)
- [All Contributors](https://github.com/thephpleague/dic/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/thephpleague/dic/blob/master/LICENSE.md) for more information.
