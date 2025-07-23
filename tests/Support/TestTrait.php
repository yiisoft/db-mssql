<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Dsn;
use Yiisoft\Db\Mssql\Driver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';
    private string $fixture = 'mssql.sql';

    public static function setUpBeforeClass(): void
    {
        $db = self::getDb();

        DbHelper::loadFixture($db, __DIR__ . '/Fixture/mssql.sql');

        $db->close();
    }

    protected function getConnection(bool $fixture = false): Connection
    {
        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$this->fixture");
        }

        return $db;
    }

    protected static function getDb(): Connection
    {
        $dsn = (string) new Dsn(
            host: self::getHost(),
            databaseName: self::getDatabaseName(),
            port: self::getPort(),
            options: ['Encrypt' => 'no']
        );

        return new Connection(
            new Driver($dsn, self::getUsername(), self::getPassword()),
            DbHelper::getSchemaCache(),
        );
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (string) new Dsn(
                host: self::getHost(),
                databaseName: self::getDatabaseName(),
                port: self::getPort(),
                options: ['Encrypt' => 'no']
            );
        }

        return $this->dsn;
    }

    protected static function getDriverName(): string
    {
        return 'sqlsrv';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    protected function setFixture(string $fixture): void
    {
        $this->fixture = $fixture;
    }

    protected function getDriver(): Driver
    {
        return new Driver($this->getDsn(), self::getUsername(), self::getPassword());
    }

    private static function getDatabaseName(): string
    {
        return getenv('YII_MSSQL_DATABASE') ?: 'yiitest';
    }

    private static function getHost(): string
    {
        return getenv('YII_MSSQL_HOST') ?: '127.0.0.1';
    }

    private static function getPort(): string
    {
        return getenv('YII_MSSQL_PORT') ?: '1433';
    }

    private static function getUsername(): string
    {
        return getenv('YII_MSSQL_USER') ?: 'SA';
    }

    private static function getPassword(): string
    {
        return getenv('YII_MSSQL_PASSWORD') ?: 'YourStrong!Passw0rd';
    }
}
