<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Psr\Log\LoggerInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Profiler\ProfilerInterface;

trait TestTrait
{
    protected function getCache(): CacheInterface
    {
        return MssqlConnection::getCache();
    }

    /**
     * @throws Exception
     */
    protected function getConnection(): ConnectionPDOInterface
    {
        return MssqlConnection::getConnection();
    }

    /**
     * @throws Exception
     */
    protected function getConnectionWithData(): ConnectionPDOInterface
    {
        return MssqlConnection::getConnection(true);
    }

    /**
     * @throws Exception
     */
    protected function getConnectionWithDsn(string $dsn): ConnectionPDOInterface
    {
        return MssqlConnection::getConnection(false, $dsn);
    }

    /**
     * @throws Exception
     */
    protected function getQuoter(): QuoterInterface
    {
        return MssqlConnection::getConnection()->getQuoter();
    }

    protected function getLogger(): LoggerInterface
    {
        return MssqlConnection::getLogger();
    }

    protected function getQuery(ConnectionPDOInterface $db): Query
    {
        return new Query($db);
    }

    protected function getQueryCache(): QueryCache
    {
        return MssqlConnection::getQueryCache();
    }

    protected function getProfiler(): ProfilerInterface
    {
        return MssqlConnection::getProfiler();
    }

    protected function getSchemaCache(): SchemaCache
    {
        return MssqlConnection::getSchemaCache();
    }
}
