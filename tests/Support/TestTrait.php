<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Mssql\PdoConnection;
use Yiisoft\Db\Mssql\Dsn;
use Yiisoft\Db\Mssql\PdoDriver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';
    private string $fixture = 'mssql.sql';

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function getConnection(bool $fixture = false): ConnectionPDOInterface
    {
        $db = new PdoConnection(
            new PdoDriver($this->getDsn(), 'SA', 'YourStrong!Passw0rd'),
            DbHelper::getSchemaCache()
        );

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$this->fixture");
        }

        return $db;
    }

    protected static function getDb(): ConnectionPDOInterface
    {
        $dsn = (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString();

        return new PdoConnection(
            new PdoDriver($dsn, 'SA', 'YourStrong!Passw0rd'),
            DbHelper::getSchemaCache(),
        );
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString();
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
}
