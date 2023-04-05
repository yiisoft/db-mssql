<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Driver\PDO\AbstractConnectionPDO;
use Yiisoft\Db\Driver\PDO\CommandPDOInterface;
use Yiisoft\Db\Query\BatchQueryResultInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for MSSQL Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-sqlsrv.php
 */
final class Connection extends AbstractConnectionPDO
{
    public function createBatchQueryResult(QueryInterface $query, bool $each = false): BatchQueryResultInterface
    {
        return new BatchQueryResult($query, $each);
    }

    public function createCommand(string $sql = null, array $params = []): CommandPDOInterface
    {
        $command = new Command($this);

        if ($sql !== null) {
            $command->setSql($sql);
        }

        if ($this->logger !== null) {
            $command->setLogger($this->logger);
        }

        if ($this->profiler !== null) {
            $command->setProfiler($this->profiler);
        }

        return $command->bindValues($params);
    }

    public function createTransaction(): TransactionInterface
    {
        return new Transaction($this);
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder(
                $this->getQuoter(),
                $this->getSchema(),
            );
        }

        return $this->queryBuilder;
    }

    public function getQuoter(): QuoterInterface
    {
        if ($this->quoter === null) {
            $this->quoter = new Quoter(['[', ']'], ['[', ']'], $this->getTablePrefix());
        }

        return $this->quoter;
    }

    public function getSchema(): SchemaInterface
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this, $this->schemaCache);
        }

        return $this->schema;
    }

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established.
     */
    protected function initConnection(): void
    {
        $this->pdo = $this->driver->createConnection();
    }
}
