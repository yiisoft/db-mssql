<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\Connection as AbstractConnection;

use function in_array;

/**
 * Database connection class prefilled for MSSQL Server.
 */
final class Connection extends AbstractConnection
{
    private bool $isSybase = false;
    private QueryCache $queryCache;
    private SchemaCache $schemaCache;

    public function __construct(string $dsn, QueryCache $queryCache, SchemaCache $schemaCache)
    {
        $this->queryCache = $queryCache;
        $this->schemaCache = $schemaCache;

        parent::__construct($dsn, $queryCache);
    }

    public function createCommand(?string $sql = null, array $params = []): Command
    {
        if ($sql !== null) {
            $sql = $this->quoteSql($sql);
        }

        $command = new Command($this, $this->queryCache, $sql);

        if ($this->logger !== null) {
            $command->setLogger($this->logger);
        }

        if ($this->profiler !== null) {
            $command->setProfiler($this->profiler);
        }

        return $command->bindValues($params);
    }

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    public function getSchema(): Schema
    {
        return new Schema($this, $this->schemaCache);
    }

    public function isSybase(): bool
    {
        return $this->isSybase;
    }

    /**
     * @param bool $value set the database connected via pdo_dblib is SyBase, for default it's false.
     */
    public function sybase(bool $value): void
    {
        $this->isSybase = $value;
    }

    /**
     * Returns the name of the DB driver.
     *
     * @return string name of the DB driver
     */
    public function getDriverName(): string
    {
        return 'sqlsrv';
    }

    protected function initConnection(): void
    {
        $pdo = $this->getPDO() ?? $this->createPdoInstance();
        $pdo->setAttribute(SqlsrvPDO::ATTR_ERRMODE, SqlsrvPDO::ERRMODE_EXCEPTION);

        if (!$this->isSybase && in_array($this->getDriverName(), ['mssql', 'dblib'], true)) {
            $pdo->exec('SET ANSI_NULL_DFLT_ON ON');
        }

        $this->setPdo($pdo);
    }

    private function createPdoInstance(): SqlsrvPDO
    {
        return new SqlsrvPDO($this->getDsn(), $this->getUsername(), $this->getPassword(), $this->getAttributes());
    }
}
