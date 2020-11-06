<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

final class Dsn
{
    private string $databaseName;
    private string $driver;
    private string $server;
    private string $port;

    public function __construct(string $driver, string $server, string $databaseName, string $port = '1433')
    {
        $this->driver = $driver;
        $this->server = $server;
        $this->databaseName = $databaseName;
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
     * $connection = new MssqlConnection($this->cache, $this->logger, $this->profiler, $dsn->asString());
     * ```
     *
     * Will result in the DSN string `sqlsrv:Server=localhost,1433;Database=yiitest`.
     */
    public function asString(): string
    {
        $dsn = "$this->driver:" . "Server=$this->server" . ";Database=$this->databaseName";

        if ($this->port !== null) {
            $dsn = "$this->driver:" . "Server=$this->server," . "$this->port" . ";Database=$this->databaseName";
        }

        return $dsn;
    }

    public function __toString(): string
    {
        return $this->asString();
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}
