<p align="center">
    <img src="https://raw.githubusercontent.com/cndrsdrmn/php-scaffold/master/docs/example.png" height="300" alt="PHP Scaffold">
</p>
<p align="center">
    <a href="https://github.com/cndrsdrmn/php-scaffold/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/cndrsdrmn/php-scaffold/actions/workflows/tests.yml/badge.svg"></a>
    <a href="https://packagist.org/packages/cndrsdrmn/php-scaffold"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/cndrsdrmn/php-scaffold"></a>
    <a href="https://packagist.org/packages/cndrsdrmn/php-scaffold"><img alt="Latest Version" src="https://img.shields.io/packagist/v/cndrsdrmn/php-scaffold"></a>
    <a href="https://github.com/cndrsdrmn/php-scaffold/blob/master/LICENSE"><img alt="License" src="https://img.shields.io/github/license/cndrsdrmn/php-scaffold"></a>
</p>

# PHP Scaffold

A boilerplate for modern PHP libraries. This repository provides a clean, opinionated starting point for building,
testing, and maintaining PHP packages.

- PHP 8.2+
- Pre-configured testing ([PHPUnit](https://github.com/sebastianbergmann/phpunit))
- Static analysis ([PHPStan](https://github.com/phpstan/phpstan))
- Code style ([Laravel Pint](https://github.com/laravel/pint))
- Automated refactoring ([Rector](https://github.com/rectorphp/rector))
- GitHub Actions workflow for CI

## Quick start

- Use as a GitHub template:
  > Click "Use this template" on the repository page to create your new package with this setup.
- Use Composer to scaffold a new package:
  ```shell
  composer create-project cndrsdrmn/php-scaffold --prefer-source my-package
  ```

## Contributing

Contributions are welcome! Please read the [contribution guidelines](./CONTRIBUTING.md) first.

**PHP Scaffold** was created by [**Candra Sudirman**](https://github.com/cndrsdrmn) under the [**MIT license**](https://github.com/cndrsdrmn/php-scaffold/blob/master/LICENSE).