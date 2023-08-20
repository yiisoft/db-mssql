<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use PDOException;
use Throwable;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Implements a database command that can be executed against a PDO (PHP Data Object) database connection for MSSQL
 * Server.
 */
final class Command extends AbstractPdoCommand
{
    public function showDatabases(): array
    {
        $sql = <<<SQL
        SELECT [name] FROM [sys].[databases] WHERE [name] NOT IN ('master', 'tempdb', 'model', 'msdb')
        SQL;

        return $this->setSql($sql)->queryColumn();
    }

    /**
     * @psalm-suppress UnusedClosureParam
     *
     * @throws Exception
     * @throws Throwable
     */
    protected function internalExecute(string|null $rawSql): void
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
                        fn () => $this->internalExecute($rawSql),
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

    protected function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }
}
