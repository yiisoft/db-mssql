<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Stringable;

/**
 * Represents a Data Source Name (DSN) for a MSSQL Server that's used to configure a {@see Driver} instance.
 *
 * To get DSN in string format, use the `(string)` type casting operator.
 *
 * @link https://www.php.net/manual/en/ref.pdo-sqlsrv.connection.php
 */
final class Dsn implements Stringable
{
    /**
     * @param string $driver The database driver name.
     * @param string $host The database host name or IP address.
     * @param string $databaseName The database name to connect to. Empty string if isn't set.
     * @param string $port The database port. Empty string if isn't set.
     * @param string[] $options The database connection options. Default value to an empty array.
     *
     * @psalm-param array<string,string> $options
     */
    public function __construct(
        public readonly string $driver = 'sqlsrv',
        public readonly string $host = '127.0.0.1',
        public readonly string $databaseName = '',
        public readonly string $port = '1433',
        public readonly array $options = [],
    ) {
    }

    /**
     * @return string the Data Source Name, or DSN, has the information required to connect to the database.
     * Please refer to the [PHP manual](https://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` property is used as the driver prefix of the DSN. For example:
     *
     * ```php
     * $dsn = new Dsn('sqlsrv', 'localhost', 'yiitest', '1433');
     * $driver = new Driver($dsn, 'username', 'password');
     * $db = new Connection($driver, $schemaCache);
     * ```
     *
     * Will result in the DSN string `sqlsrv:Server=localhost,1433;Database=yiitest`.
     */
    public function __toString(): string
    {
        $dsn = "$this->driver:Server=$this->host";

        if ($this->port !== '') {
            $dsn .= ",$this->port";
        }

        if ($this->databaseName !== '') {
            $dsn .= ";Database=$this->databaseName";
        }

        foreach ($this->options as $key => $value) {
            $dsn .= ";$key=$value";
        }

        return $dsn;
    }
}
