<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\PDO;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Transaction\TransactionPDO;

final class TransactionPDOMssql extends TransactionPDO
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
     *
     * @throws NotSupportedException
     */
    public function releaseSavepoint(string $name): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * Rolls back to a previously created savepoint.
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
