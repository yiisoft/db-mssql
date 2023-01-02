<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * The Dsn class is typically used to parse a DSN string, which is a string that contains all the necessary information
 * to connect to a database SQL Server, such as the database driver, host, database name, port.
 *
 * It also allows you to access individual components of the DSN, such as the driver, host, database name or port.
 *
 * @link https://www.php.net/manual/en/ref.pdo-sqlsrv.connection.php
 */
final class Dsn extends AbstractDsn
{
    public function __construct(
        private string $driver,
        private string $host,
        private string $databaseName,
        private string $port = '1433'
    ) {
        parent::__construct($driver, $host, $databaseName, $port);
    }

    /**
     * @return string the Data Source Name, or DSN, contains the information required to connect to the database.
     * Please refer to the [PHP manual](http://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` array key is used as the driver prefix of the DSN, all further key-value pairs are rendered as
     * `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new Dsn('sqlsrv', 'localhost', 'yiitest', '1433');
     * $db = new ConnectionPDO(new PDODriver($dsn->asString(), 'username', 'password'), $queryCache, $schemaCache);
     * ```
     *
     * Will result in the DSN string `sqlsrv:Server=localhost,1433;Database=yiitest`.
     */
    public function asString(): string
    {
        return match ($this->port) {
            '' => "$this->driver:" . "Server=$this->host;" . "Database=$this->databaseName",
            default => "$this->driver:" . "Server=$this->host,$this->port;" . "Database=$this->databaseName",
        };
    }
}
