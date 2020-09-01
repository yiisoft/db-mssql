<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Connection;

use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Mssql\Pdo\PDO;
use Yiisoft\Db\Mssql\Pdo\SqlsrvPDO;
use Yiisoft\Db\Mssql\Schema\MssqlSchema;

use function in_array;

/**
 * Database connection class prefilled for MSSQL Server.
 */
final class MssqlConnection extends Connection
{
    private bool $isSybase = false;
    private ?MssqlSchema $schema = null;

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
     * @return MssqlSchema the schema information for the database opened by this connection.
     */
    public function getSchema(): MssqlSchema
    {
        if ($this->schema !== null) {
            return $this->schema;
        }

        return $this->schema = new MssqlSchema($this);
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
}
