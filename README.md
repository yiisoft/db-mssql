<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/6154722" height="100px">
    </a>
    <h1 align="center">MSSQL Server Extension for Yii 3</h1>
    <br>
</p>

This extension provides the MSSQL Server support for the [Yii framework 3 ](http://www.yiiframework.com).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-mssql/v/stable.png)](https://packagist.org/packages/yiisoft/db-mssql)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-mssql/downloads.png)](https://packagist.org/packages/yiisoft/db-mssql)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/db-mssql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/db-mssql/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/db-mssql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/db-mssql/?branch=master)


## Support version

|  PHP | Mssql Version            |  CI-Actions
|:----:|:------------------------:|:---:|
|**7.4 - 8.0**| **2017 - 2019**|[![Build status](https://github.com/yiisoft/db-mssql/workflows/build/badge.svg)](https://github.com/yiisoft/db-mssql/actions/runs/239488310) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-mssql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-mssql/master) [![static analysis](https://github.com/yiisoft/db-mssql/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-mssql/actions?query=workflow%3A%22static+analysis%22) [![type-coverage](https://shepherd.dev/github/yiisoft/db-mssql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-mssql)


## Installation

The package could be installed via composer:

```php
composer require yiisoft/db-mssql
```

## Configuration

Di-Container:

```php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Dsn;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileRotatorInterface;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Profiler\Profiler;

return [
    ContainerInterface::class => static function (ContainerInterface $container) {
        return $container;
    },

    Aliases::class => [
        '@root' => dirname(__DIR__, 1), // directory / packages.
        '@runtime' => '@root/runtime'
    ],

    CacheInterface::class => static function () {
        return new Cache(new ArrayCache());
    },

    FileRotatorInterface::class => static function () {
        return new FileRotator(10);
    },

    LoggerInterface::class => static function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $fileRotator = $container->get(FileRotatorInterface::class);

        $fileTarget = new FileTarget(
            $aliases->get('@runtime/logs/app.log'),
            $fileRotator
        );

        $fileTarget->setLevels(
            [
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::INFO,
                LogLevel::DEBUG
            ]
        );

        return new Logger(['file' => $fileTarget]);
    },

    Profiler::class => static function (ContainerInterface $container) {
        return new Profiler($container->get(LoggerInterface::class));
    },

    Dsn::class => static function () use ($params) {
        return new Dsn(
            $params['yiisoft/db-mssql']['dsn']['driver'],
            $params['yiisoft/db-mssql']['dsn']['server'],
            $params['yiisoft/db-mssql']['dsn']['database'],
            $params['yiisoft/db-mssql']['dsn']['port'],
        );
    },

    ConnectionInterface::class  => static function (ContainerInterface $container) use ($params) {
        $connection = new Connection(
            $container->get(CacheInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(Profiler::class),
            $container->get(Dsn::class)->getDsn(),
        );

        $connection->setUsername($params['yiisoft/db-mssql']['username']);
        $connection->setPassword($params['yiisoft/db-mssql']['password']);

        return $connection;
    }
];
```

Params.php

```php
return [
    'yiisoft/db-mssql' => [
        'dsn' => [
            'driver' => 'sqlsrv',
            'server' => '127.0.0.1',
            'database' => 'yiitest',
            'port' => '1433'
        ],
        'username' => 'SA',
        'password' => 'YourStrong!Passw0rd'
    ]
];
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

Note: You must have MSSQL installed to run the tests, it supports all MSSQL versions.

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/docs/). To run static analysis:

```php
./vendor/bin/psalm
```
