<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Driver;
use Yiisoft\Db\Mssql\Dsn;
use Yiisoft\Db\Tests\Support\TestHelper;

final class TestConnection
{
    private static ?string $dsn = null;
    private static ?Connection $connection = null;

    public static function getShared(): Connection
    {
        $db = self::$connection ??= self::create();
        $db->getSchema()->refresh();
        return $db;
    }

    public static function dsn(): string
    {
        return self::$dsn ??= (string) new Dsn(
            host: self::host(),
            databaseName: self::databaseName(),
            port: self::port(),
            options: ['Encrypt' => 'no'],
        );
    }

    public static function create(?string $dsn = null): Connection
    {
        return new Connection(self::createDriver($dsn), TestHelper::createMemorySchemaCache());
    }

    public static function createDriver(?string $dsn = null): Driver
    {
        return new Driver($dsn ?? self::dsn(), self::username(), self::password());
    }

    public static function databaseName(): string
    {
        return getenv('YII_MSSQL_DATABASE') ?: 'yiitest';
    }

    private static function host(): string
    {
        return getenv('YII_MSSQL_HOST') ?: '127.0.0.1';
    }

    private static function port(): string
    {
        return getenv('YII_MSSQL_PORT') ?: '1433';
    }

    private static function username(): string
    {
        return getenv('YII_MSSQL_USER') ?: 'SA';
    }

    private static function password(): string
    {
        return getenv('YII_MSSQL_PASSWORD') ?: 'YourStrong!Passw0rd';
    }
}
