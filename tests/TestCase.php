<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Exception;
use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Driver\PDODriver;
use Yiisoft\Db\Mssql\PDO\ConnectionPDOMssql;
use Yiisoft\Db\TestSupport\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected string $drivername = 'sqlsrv';
    protected string $dsn = 'sqlsrv:Server=127.0.0.1,1433;Database=yiitest';
    protected string $username = 'SA';
    protected string $password = 'YourStrong!Passw0rd';
    protected string $charset = 'UTF8MB4';
    protected array $dataProvider;
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected ?ConnectionPDOMssql $db = null;

    /**
     * @param bool $reset whether to clean up the test database.
     * @param string|null $dsn
     * @param string $fixture
     *
     * @return ConnectionPDOMssql
     */
    protected function getConnection(
        bool $reset = false,
        ?string $dsn = null,
        string $fixture = __DIR__ . '/Fixture/mssql.sql'
    ): ConnectionPDOMssql {
        $pdoDriver = new PDODriver($dsn ?? $this->dsn, $this->username, $this->password);
        $this->db = new ConnectionPDOMssql($pdoDriver, $this->createQueryCache(), $this->createSchemaCache());
        $this->db->setLogger($this->createLogger());
        $this->db->setProfiler($this->createProfiler());

        if ($reset === false) {
            return $this->db;
        }

        try {
            $this->prepareDatabase($this->db, $fixture);
        } catch (Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->db;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db?->close();
        unset(
            $this->cache,
            $this->db,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }
}
