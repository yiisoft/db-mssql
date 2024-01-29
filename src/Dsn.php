<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * Implement a Data Source Name (DSN) for an MSSQL Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-sqlsrv.connection.php
 */
final class Dsn extends AbstractDsn
{
    public function __construct(
        private string $driver,
        private string $host,
        private string|null $databaseName = null,
        private string $port = '1433'
    ) {
        parent::__construct($driver, $host, $databaseName, $port);
    }

    /**
     * @return string the Data Source Name, or DSN, has the information required to connect to the database.
     * Please refer to the [PHP manual](http://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` array key is used as the driver prefix of the DSN, all further key-value pairs are rendered as
     * `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new Dsn('sqlsrv', 'localhost', 'yiitest', '1433');
     * $driver = new Driver($dsn->asString(), 'username', 'password');
     * $db = new Connection($driver, $schemaCache);
     * ```
     *
     * Will result in the DSN string `sqlsrv:Server=localhost,1433;Database=yiitest`.
     */
    public function asString(): string
    {
        if ($this->port !== '') {
            $server = "Server=$this->host,$this->port;";
        } else {
            $server = "Server=$this->host;";
        }

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (!empty($this->databaseName)) {
            $dsn = "$this->driver:" . $server . "Database=$this->databaseName";
        } else {
            $dsn = "$this->driver:" . $server;
        }

        return $dsn;
    }
}
