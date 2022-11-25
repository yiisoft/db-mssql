<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Mssql\ConnectionPDO;
use Yiisoft\Db\Mssql\PDODriver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    protected function getConnection(string ...$fixtures): ConnectionPDOInterface
    {
        $db = new ConnectionPDO(
            new PDODriver('sqlsrv:Server=127.0.0.1,1433;Database=yiitest', 'SA', 'YourStrong!Passw0rd'),
            DbHelper::getQueryCache(),
            DbHelper::getSchemaCache(),
        );

        foreach ($fixtures as $fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$fixture.sql");
        }

        return $db;
    }
}
