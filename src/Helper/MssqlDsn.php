<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Helper;

final class MssqlDsn
{
    private ?string $database;
    private string $driver;
    private string $dsn;
    private ?string $server;
    private ?string $port;

    public function __construct(string $driver, string $server, string $database, string $port = '1433')
    {
        $this->driver = $driver;
        $this->server = $server;
        $this->database = $database;
        $this->port = $port;
    }

    /**
     * @return string the Data Source Name, or DSN, contains the information required to connect to the database.
     * Please refer to the [PHP manual](http://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` array key is used as the driver prefix of the DSN, all further key-value pairs are rendered as
     * `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new MssqlDsn('sqlsrv', 'localhost', 'yiitest', '1433');
     * $connection = new MssqlConnection($this->cache, $this->logger, $this->profiler, $dsn->getDsn());
     * ```
     *
     * Will result in the DSN string `sqlsrv:Server=localhost,1433;Database=yiitest`.
     */

    public function getDsn(): string
    {
        $this->dsn = "$this->driver:" . "Server=$this->server," . "$this->port;" . "Database=$this->database";

        return $this->dsn;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}
