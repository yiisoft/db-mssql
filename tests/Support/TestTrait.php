<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Dsn;
use Yiisoft\Db\Mssql\Driver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';
    private string $fixture = 'mssql.sql';

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function getConnection(bool $fixture = false): PdoConnectionInterface
    {
        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$this->fixture");
        }

        return $db;
    }

    protected static function getDb(): PdoConnectionInterface
    {
        $dsn = (new Dsn(
            host: self::getHost(),
            databaseName: self::getDatabaseName(),
            port: self::getPort(),
            options: ['Encrypt' => 'no']
        ))->asString();

        return new Connection(
            new Driver($dsn, 'SA', 'YourStrong!Passw0rd'),
            DbHelper::getSchemaCache(),
        );
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn(
                host: self::getHost(),
                databaseName: self::getDatabaseName(),
                port: self::getPort(),
                options: ['Encrypt' => 'no']
            ))->asString();
        }

        return $this->dsn;
    }

    protected function getDriverName(): string
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

    private function getDriver(): PdoDriverInterface
    {
        return new Driver($this->getDsn(), self::getUsername(), self::getPassword());
    }

    private static function getDatabaseName(): string
    {
        return getenv('YII_MSSQL_DATABASE');
    }

    private static function getHost(): string
    {
        return getenv('YII_MSSQL_HOST');
    }

    private static function getPort(): string
    {
        return getenv('YII_MSSQL_PORT');
    }

    private static function getUsername(): string
    {
        return getenv('YII_MSSQL_USER');
    }

    private static function getPassword(): string
    {
        return getenv('YII_MSSQL_PASSWORD');
    }
}
