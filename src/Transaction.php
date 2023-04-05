<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * Implements the MSSQL Server specific transaction.
 */
final class Transaction extends \Yiisoft\Db\Driver\Pdo\AbstractTransaction
{
    /**
     * Creates a new savepoint.
     *
     * @param string $name the savepoint name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function createSavepoint(string $name): void
    {
        $this->db->createCommand("SAVE TRANSACTION $name")->execute();
    }

    /**
     * Releases an existing savepoint.
     *
     * @param string $name the savepoint name.
     */
    public function releaseSavepoint(string $name): void
    {
        // does nothing as MSSQL doesn't support this
    }

    /**
     * Rolls back to a before created savepoint.
     *
     * @param string $name the savepoint name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function rollBackSavepoint(string $name): void
    {
        $this->db->createCommand("ROLLBACK TRANSACTION $name")->execute();
    }
}
