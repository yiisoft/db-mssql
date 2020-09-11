<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Connection\Connection as AbstractConnection;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Mssql\PDO;
use Yiisoft\Db\Mssql\SqlsrvPDO;
use Yiisoft\Db\Mssql\Schema;

use function in_array;

/**
 * Database connection class prefilled for MSSQL Server.
 */
final class Connection extends AbstractConnection
{
    private bool $isSybase = false;
    private ?Schema $schema = null;

    public function createCommand(?string $sql = null, array $params = []): Command
    {
        if ($sql !== null) {
            $sql = $this->quoteSql($sql);
        }

        $command = new Command($this->getProfiler(), $this->getLogger(), $this, $sql);

        return $command->bindValues($params);
    }

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    public function getSchema(): Schema
    {
        if ($this->schema !== null) {
            return $this->schema;
        }

        return $this->schema = new Schema($this);
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

    protected function createPdoInstance(): \PDO
    {
        if ($this->getDriverName() === 'sqlsrv') {
            $pdo = new SqlsrvPDO($this->getDsn(), $this->getUsername(), $this->getPassword(), $this->getAttributes());
        } else {
            $pdo = new PDO($this->getDsn(), $this->getUsername(), $this->getPassword(), $this->getAttributes());
        }

        return $pdo;
    }

    protected function initConnection(): void
    {
        $this->getPDO()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!$this->isSybase && in_array($this->getDriverName(), ['mssql', 'dblib'], true)) {
            $this->getPDO()->exec('SET ANSI_NULL_DFLT_ON ON');
        }
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
}
