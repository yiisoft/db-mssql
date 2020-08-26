<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Pdo;

/**
 * This is an extension of the default PDO class of SQLSRV driver. It provides workarounds for improperly implemented
 * functionalities of the SQLSRV driver.
 */
final class SqlsrvPDO extends \PDO
{
    /**
     * Returns value of the last inserted ID.
     *
     * SQLSRV driver implements {@see PDO::lastInsertId()} method but with a single peculiarity:
     *
     * When `$sequence` value is a null or an empty string it returns an empty string.
     * But when parameter is not specified it works as expected and returns actual
     * last inserted ID (like the other PDO drivers).
     *
     * @param string|null $sequence the sequence name. Defaults to null.
     *
     * @return string last inserted ID value.
     */
    public function lastInsertId($sequence = null): string
    {
        return !$sequence ? parent::lastInsertId() : parent::lastInsertId($sequence);
    }
}
