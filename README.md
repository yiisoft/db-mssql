<p align="center">
        <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <a href="https://www.microsoft.com/sql-server" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/6154722" height="100px">
    </a>
    <h1 align="center">Yii Database MSSQL Extension</h1>
    <br>
</p>

Yii Database MSSQL Extension is a package for working with [MSSQL] databases in PHP. It is a part of the [Yii Framework], a high-performance, open-source PHP framework for web application development.

The package provides a set of classes for connecting to a [MSSQL] database, creating and executing commands, and working with data. It also includes a set of tools for building and executing queries, including support for parameter binding, as well as tools for working with transactions.

To use Yii Database MSSQL Extension, you will need to have the PHP [MSSQL extension] installed and enabled on your server. You will also need to have access to a SQL Server database and the necessary credentials to connect to it.

It is used in [Yii Framework] but can be used separately.

[MSSQL]: https://www.microsoft.com/sql-server
[MSSQL extension]: https://pecl.php.net/package/sqlsrv
[Yii Framework]: https://www.yiiframework.com/

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-mssql/v/stable.png)](https://packagist.org/packages/yiisoft/db-mssql)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-mssql/downloads.png)](https://packagist.org/packages/yiisoft/db-mssql)
[![rector](https://github.com/yiisoft/db-mssql/actions/workflows/rector.yml/badge.svg)](https://github.com/yiisoft/db-mssql/actions/workflows/rector.yml)
[![codecov](https://codecov.io/gh/yiisoft/db-mssql/branch/master/graph/badge.svg?token=UF9VERNMYU)](https://codecov.io/gh/yiisoft/db-mssql)
[![StyleCI](https://github.styleci.io/repos/114756477/shield?branch=master)](https://github.styleci.io/repos/114756477?branch=master)

### Support version

|  PHP | Mssql Version            |  CI-Actions
|:----:|:------------------------:|:---:|
|**8.0 - 8.2**| **2017 - 2022**|[![build](https://github.com/yiisoft/db-mssql/actions/workflows/build.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-mssql/actions/workflows/build.yml) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-mssql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-mssql/master) [![static analysis](https://github.com/yiisoft/db-mssql/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-mssql/actions/workflows/static.yml) [![type-coverage](https://shepherd.dev/github/yiisoft/db-mssql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-mssql)

### Installation

The package could be installed via composer:

```php
composer require yiisoft/db-mssql
```

## Usage 

For config connection to MSSQL database check [Connecting MSSQL](https://github.com/yiisoft/db/blob/master/docs/en/connection/mssql.md).

[Check the documentation docs](https://github.com/yiisoft/db/blob/master/docs/en/getting-started.md) to learn about usage.

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Rector

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or 
use either newest or any specific version of PHP: 

```shell
./vendor/bin/rector
```

### Composer require checker

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all dependencies are correctly defined in `composer.json`.

To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

### License

The MSSQL Server Extension for Yii 3 is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
