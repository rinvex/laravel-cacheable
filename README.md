# Rinvex Cacheable

**Rinvex Cacheable** is a granular, intuitive, and fluent caching system for eloquent models. Simple, but yet powerful, plug-n-play with no hassle.

[![Packagist](https://img.shields.io/packagist/v/rinvex/laravel-cacheable.svg?label=Packagist&style=flat-square)](https://packagist.org/packages/rinvex/laravel-cacheable)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/rinvex/laravel-cacheable.svg?label=Scrutinizer&style=flat-square)](https://scrutinizer-ci.com/g/rinvex/laravel-cacheable/)
[![Travis](https://img.shields.io/travis/rinvex/laravel-cacheable.svg?label=TravisCI&style=flat-square)](https://travis-ci.org/rinvex/laravel-cacheable)
[![StyleCI](https://styleci.io/repos/79321486/shield)](https://styleci.io/repos/79321486)
[![License](https://img.shields.io/packagist/l/rinvex/laravel-cacheable.svg?label=License&style=flat-square)](https://github.com/rinvex/laravel-cacheable/blob/develop/LICENSE)

What this package do -technically- caching eloquent query passing through the `get` method, whatever it is and it's smart enough to indicated any conditions, limit, offset, wheres, orders, groups, ..etc and take that criteria into account when caching and checking for cached version. Also by default any create, update, or delete event will flush all cache for that specific model. It uses default Laravel caching system, and utilizes whatever cache driver you are using. Awesome, right?


## Installation & Usage

1. Install the package via composer:
    ```shell
    composer require rinvex/laravel-cacheable
    ```

2. Use the `\Rinvex\Cacheable\CacheableEloquent` in your desired model, and you're done!

3. Seriously, that's it!

Check the [`CacheableEloquent`](src/CacheableEloquent.php) source code for more awesome stuff if you need advanced control.


## Optional Features

You can optionally override model caching behaviour per model as follows:

```php
    /**
     * Indicate if the model cache clear is enabled.
     *
     * @var bool
     */
    protected $cacheClearEnabled = true;

    /**
     * The model cache driver.
     *
     * @var string
     */
    protected $cacheDriver = 'memcached';

    /**
     * The model cache lifetime.
     *
     * @var int
     */
    protected $cacheLifetime = -1;
```


## Changelog

Refer to the [Changelog](CHANGELOG.md) for a full history of the project.


## Support

The following support channels are available at your fingertips:

- [Chat on Slack](https://bit.ly/rinvex-slack)
- [Help on Email](mailto:help@rinvex.com)
- [Follow on Twitter](https://twitter.com/rinvex)


## Contributing & Protocols

Thank you for considering contributing to this project! The contribution guide can be found in [CONTRIBUTING.md](CONTRIBUTING.md).

Bug reports, feature requests, and pull requests are very welcome.

- [Versioning](CONTRIBUTING.md#versioning)
- [Pull Requests](CONTRIBUTING.md#pull-requests)
- [Coding Standards](CONTRIBUTING.md#coding-standards)
- [Feature Requests](CONTRIBUTING.md#feature-requests)
- [Git Flow](CONTRIBUTING.md#git-flow)


## Security Vulnerabilities

If you discover a security vulnerability within this project, please send an e-mail to [help@rinvex.com](help@rinvex.com). All security vulnerabilities will be promptly addressed.


## About Rinvex

Rinvex is a software solutions startup, specialized in integrated enterprise solutions for SMEs established in Alexandria, Egypt since June 2016. We believe that our drive The Value, The Reach, and The Impact is what differentiates us and unleash the endless possibilities of our philosophy through the power of software. We like to call it Innovation At The Speed Of Life. That’s how we do our share of advancing humanity.


## License

This software is released under [The MIT License (MIT)](LICENSE).

(c) 2016-2020 Rinvex LLC, Some rights reserved.
