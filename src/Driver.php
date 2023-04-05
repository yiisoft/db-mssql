<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use PDO;

/**
 * Implements the MSSQL Server driver based on the PDO (PHP Data Objects) extension.
 *
 * @link https://www.php.net/manual/en/ref.pdo-sqlsrv.php
 */
final class Driver extends \Yiisoft\Db\Driver\Pdo\AbstractDriver
{
    public function createConnection(): PDO
    {
        $this->attributes += [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        return parent::createConnection();
    }

    public function getDriverName(): string
    {
        return 'sqlsrv';
    }
}
