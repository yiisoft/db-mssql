<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\TestUtility\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected const DB_DSN = 'sqlsrv:Server=127.0.0.1,1433;Database=yiitest';
    protected const DB_FIXTURES_PATH = __DIR__ . '/Fixture/mssql.sql';
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

    protected function createConnection(string $dsn = null): ?ConnectionInterface
    {
        $db = null;

        if ($dsn !== null) {
            $db = new Connection($dsn, $this->createQueryCache(), $this->createSchemaCache());
            $db->setLogger($this->createLogger());
            $db->setProfiler($this->createProfiler());
            $db->setUsername('SA');
            $db->setPassword('YourStrong!Passw0rd');
            $db->setCharset('UTF8');
        }

        return $db;
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param string $sql
     *
     * @return string
     */
    protected function replaceQuotes(string $sql): string
    {
        return str_replace(['[[', ']]'], ['[', ']'], $sql);
    }
}
