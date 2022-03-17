<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

/**
 * This is an extension of the default PDO class of SQLSERVER driver. It provides workarounds for improperly implemented
 * functionalities of the SQLSERVER driver.
 */
final class SqlsrvPDO extends \PDO
{
    /**
     * Returns value of the last inserted ID.
     *
     * SQLSERVER driver implements {@see PDO::lastInsertId()} method but with a single peculiarity:
     *
     * When `$sequence` value is a null or an empty string it returns an empty string.
     * But when parameter is not specified it works as expected and returns actual
     * last inserted ID (like the other PDO drivers).
     *
     * @param string|null $name the sequence name. Defaults to null.
     *
     * @return string last inserted ID value.
     */
    public function lastInsertId(string $name = null): string
    {
        return !$name ? parent::lastInsertId() : parent::lastInsertId($name);
    }
}
