<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use PDOException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Query\BatchQueryResult as BaseBatchQueryResult;

class BatchQueryResult extends BaseBatchQueryResult
{
    /**
     * @var int MSSQL error code for exception that is thrown when last batch is size less than specified batch size
     *
     * {@see https://github.com/yiisoft/yii2/issues/10023}
     */
    private int $mssqlNoMoreRowsErrorCode = -13;

    /**
     * Reads and collects rows for batch.
     *
     * @throws InvalidCallException
     *
     * @return array
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
            if (($e->errorInfo[1] ?? null) !== $this->mssqlNoMoreRowsErrorCode) {
                throw $e;
            }
        }

        return $rows;
    }
}
