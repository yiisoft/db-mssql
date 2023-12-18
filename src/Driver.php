<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use PDO;
use Yiisoft\Db\Driver\Pdo\AbstractPdoDriver;

/**
 * Implements the MSSQL Server driver based on the PDO (PHP Data Objects) extension.
 *
 * @link https://www.php.net/manual/en/ref.pdo-sqlsrv.php
 */
final class Driver extends AbstractPdoDriver
{
    public function createConnection(): PDO
    {
        $this->attributes += [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $this->attributes += [PDO::ATTR_STRINGIFY_FETCHES => false];
        
        return parent::createConnection();
    }

    public function getDriverName(): string
    {
        return 'sqlsrv';
    }
}
