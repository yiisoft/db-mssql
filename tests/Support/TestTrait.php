<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;

trait TestTrait
{
    protected function getConnection($prepareDatabase = false, string $dsn = ''): ConnectionInterface
    {
        $mssqlHelper = new MssqlHelper();
        $db = $dsn !== '' ? $mssqlHelper->createConnection($dsn) : $mssqlHelper->createConnection();

        if ($prepareDatabase) {
            $mssqlHelper->prepareDatabase($db);
        }

        return $db;
    }

    protected function getSchemaCache(): SchemaCache
    {
        $mssqlHelper = new MssqlHelper();

        return $mssqlHelper->schemaCache();
    }
}
