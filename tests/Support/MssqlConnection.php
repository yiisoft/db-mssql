<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Psr\Log\LoggerInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mssql\ConnectionPDO;
use Yiisoft\Db\Mssql\PDODriver;
use Yiisoft\Db\Tests\Support\Mock;
use Yiisoft\Profiler\ProfilerInterface;

final class MssqlConnection
{
    public static function getCache(): CacheInterface
    {
        return (new Mock())->getCache();
    }

    /**
     * @throws Exception
     */
    public static function getConnection(
        bool $prepareDatabase = false,
        string $dsn = 'sqlsrv:Server=127.0.0.1,1433;Database=yiitest',
    ): ConnectionPDO {
        $mock = new Mock();
        $pdoDriver = new PDODriver($dsn, 'SA', 'YourStrong!Passw0rd', ['chartset' => 'UTF8MB4']);
        $db = new ConnectionPDO($pdoDriver, $mock->getQueryCache(), $mock->getSchemaCache());

        if ($prepareDatabase === false) {
            return $db;
        }

        try {
            $mock->prepareDatabase($db, __DIR__ . '/Fixture/mssql.sql');
        } catch (Exception $e) {
            throw new Exception('Failed to prepare database: ' . $e->getMessage());
        }

        return $db;
    }

    public static function getLogger(): LoggerInterface
    {
        return (new Mock())->getLogger();
    }

    public static function getProfiler(): ProfilerInterface
    {
        return (new Mock())->getProfiler();
    }

    public static function getQueryCache(): QueryCache
    {
        return (new Mock())->getQueryCache();
    }

    public static function getSchemaCache(): SchemaCache
    {
        return (new Mock())->getSchemaCache();
    }
}
