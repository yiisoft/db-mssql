<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use PDOException;

use function in_array;

/**
 * Represents the result of a batch query execution for MSSQL Server.
 */
final class BatchQueryResult extends \Yiisoft\Db\Query\BatchQueryResult
{
    /**
     * @var int MSSQL error code for exception that's thrown when the last batch is size less than specified batch size
     *
     * @link https://github.com/yiisoft/yii2/issues/10023
     */
    private int $mssqlNoMoreRowsErrorCode = -13;

    /**
     * Reads and collects rows for batch.
     *
     * @psalm-suppress MixedArrayAccess
     */
    protected function getRows(): array
    {
        $rows = [];
        $count = 0;

        try {
            do {
                $this->dataReader?->next();
                /** @psalm-var array|bool $row */
                $row = $this->dataReader?->current();
            } while ($row && ($rows[] = $row) && ++$count < $this->batchSize);
        } catch (PDOException $e) {
            if (!in_array($this->mssqlNoMoreRowsErrorCode, (array) $e->errorInfo, true)) {
                throw $e;
            }
        }

        return $rows;
    }
}
