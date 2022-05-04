<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\PDO;

use PDOException;
use Yiisoft\Db\Driver\PDO\CommandPDO;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_array;

final class CommandPDOMssql extends CommandPDO
{
    /**
     * @inheritDoc
    */
    public function insertEx(string $table, array $columns): bool|array
    {
        $params = [];
        $sql = $this->queryBuilder()->insertEx($table, $columns, $params);

        $this->setSql($sql)->bindValues($params);
        $this->prepare(false);

        /** @psalm-var array|bool */
        $result = $this->queryOne();

        return is_array($result) ? $result : false;
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    public function schema(): SchemaInterface
    {
        return $this->db->getSchema();
    }

    protected function internalExecute(?string $rawSql): void
    {
        $attempt = 0;

        while (true) {
            try {
                if (
                    ++$attempt === 1
                    && $this->isolationLevel !== null
                    && $this->db->getTransaction() === null
                ) {
                    $this->db->transaction(
                        fn (string $rawSql) => $this->internalExecute($rawSql),
                        $this->isolationLevel
                    );
                } else {
                    $this->pdoStatement?->execute();
                }
                break;
            } catch (PDOException $e) {
                $rawSql = $rawSql ?: $this->getRawSql();
                $e = (new ConvertException($e, $rawSql))->run();

                if ($this->retryHandler === null || !($this->retryHandler)($e, $attempt)) {
                    throw $e;
                }
            }
        }
    }
}
