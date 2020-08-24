<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Connection;

use Yiisoft\Db\Connection\Connection as Connection;
use Yiisoft\Db\Mssql\Pdo\PDO;
use Yiisoft\Db\Mssql\Pdo\SqlsrvPDO;
use Yiisoft\Db\Mssql\Schema\MssqlSchema;

/**
 * Database connection class prefilled for MSSQL Server.
 */
final class MssqlConnection extends Connection
{
    protected array $schemaMap = [
        'sqlsrv' => Schema::class, // newer MSSQL driver on MS Windows hosts
        'mssql' => Schema::class, // older MSSQL driver on MS Windows hosts
        'dblib' => Schema::class, // dblib drivers on GNU/Linux (and maybe other OSes) hosts
    ];
    private bool $isSybase = false;
    private ?MssqlSchema $schema = null;

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException if there is no support for the current driver type
     *
     * @return Schema the schema information for the database opened by this connection.
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
        switch ($this->getDriverName()) {
            case 'sqlsrv':
                $pdo = new SqlsrvPDO(
                    $this->getDsn(),
                    $this->getUsername(),
                    $this->getPassword(),
                    $this->getAttributes()
                );
                break;

            default:
                $pdo = new PDO(
                    $this->getDsn(),
                    $this->getUsername(),
                    $this->getPassword(),
                    $this->getAttributes()
                );
                break;
        }

        return $pdo;
    }

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established.
     *
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`.
     *
     * if {@see emulatePrepare} is true, and sets the database {@see charset} if it is not empty.
     *
     * It then triggers an {@see EVENT_AFTER_OPEN} event.
     */
    protected function initConnection(): void
    {
        $this->getPDO()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!$this->isSybase && in_array($this->getDriverName(), ['mssql', 'dblib'], true)) {
            $this->getPDO()->exec('SET ANSI_NULL_DFLT_ON ON');
        }
    }
}
