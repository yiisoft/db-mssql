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
    /**
     * @psalm-param array<string,string> $options
     */
    public function __construct(
        string $driver = 'sqlsrv',
        string $host = 'localhost',
        string|null $databaseName = null,
        string|null $port = '1433',
        array $options = []
    ) {
        parent::__construct($driver, $host, $databaseName, $port, $options);
    }

    /**
     * @return string the Data Source Name, or DSN, has the information required to connect to the database.
     * Please refer to the [PHP manual](https://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
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
        $driver = $this->getDriver();
        $host = $this->getHost();
        $port = $this->getPort();
        $databaseName = $this->getDatabaseName();
        $options = $this->getOptions();

        $dsn = "$driver:Server=$host";

        if (!empty($port)) {
            $dsn .= ",$port";
        }

        if (!empty($databaseName)) {
            $dsn .= ";Database=$databaseName";
        }

        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $dsn .= ";$key=$value";
            }
        }

        return $dsn;
    }
}
