<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\TestUtility\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected const DB_CONNECTION_CLASS = \Yiisoft\Db\Mssql\Connection::class;
    protected const DB_DRIVERNAME = 'mssql';
    protected const DB_DSN = 'sqlsrv:Server=127.0.0.1,1433;Database=yiitest';
    protected const DB_FIXTURES_PATH = __DIR__ . '/Fixture/mssql.sql';
    protected const DB_USERNAME = 'SA';
    protected const DB_PASSWORD = 'YourStrong!Passw0rd';
    protected const DB_CHARSET = 'UTF8';
    protected array $dataProvider;
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->createConnection(self::DB_DSN);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->connection->close();
        unset(
            $this->cache,
            $this->connection,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }
}
