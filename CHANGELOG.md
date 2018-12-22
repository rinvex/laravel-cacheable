# Rinvex Cacheable Change Log

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](CONTRIBUTING.md).


## [v1.0.1] - 2018-12-22
- Update composer dependencies
- Add PHP 7.3 support to travis

## [v1.0.0] - 2018-10-01
- Enforce Consistency
- Support Laravel 5.7+
- Rename package to rinvex/laravel-cacheable

## [v0.0.4] - 2018-09-22
- Update travis php versions
- Fix CacheableEloquent trait attributes overridability (fix #19)
- Fix cache config defaults
- Check cache directory existence
- Drop StyleCI multi-language support (paid feature now!)
- Update composer dependencies
- Prepare and tweak testing configuration
- Update StyleCI options
- Update PHPUnit options

## [v0.0.3] - 2018-02-18
- Update supplementary files
- Update composer depedencies
- Add Laravel 5.5 support
- Support both eloquent and query builders
- Cache plucked queries
- Add PHPUnitPrettyResultPrinter
- Typehint method returns
- Fix wrong cache lifetime config after reset
- Add Laravel v5.6 support
- Tweak and enhance cacheable logic
- Drop Laravel 5.5 support
- Require PHP v7.1.3

## [v0.0.2] - 2017-03-14
- Tweak and enhance forget cache mechanism
- Update composer dependencies
- Restrict compatibility to Laravel 5.4
- Apply Laravel 5.4 event dispatcher updates
- Facilitate flexible extension and overriding
- Attach model cache tags and events to the original class, not lately static bound
- Simplify code, this is tightly coupled package with Eloquent
- Fix late static binding issues
- Fix stupid gitattributes export-ignore issues

## v0.0.1 - 2017-01-18
- Tag first release

[v1.0.1]: https://github.com/rinvex/laravel-cacheable/compare/v1.0.0...v1.0.1
[v1.0.0]: https://github.com/rinvex/laravel-cacheable/compare/v0.0.4...v1.0.0
[v0.0.4]: https://github.com/rinvex/laravel-cacheable/compare/v0.0.3...v0.0.4
[v0.0.3]: https://github.com/rinvex/laravel-cacheable/compare/v0.0.2...v0.0.3
[v0.0.2]: https://github.com/rinvex/laravel-cacheable/compare/v0.0.1...v0.0.2
