<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Support;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Mssql\ConnectionPDO;
use Yiisoft\Db\Mssql\PDODriver;
use Yiisoft\Db\Tests\Support\Connection;

use function explode;
use function file_get_contents;

final class MssqlHelper extends Connection
{
    public function createConnection(string $dsn = 'sqlsrv:Server=127.0.0.1,1433;Database=yiitest'): ConnectionInterface
    {
        $pdoDriver = new PDODriver($dsn, 'SA', 'YourStrong!Passw0rd');
        $pdoDriver->setCharset('UTF8MB4');

        return new ConnectionPDO($pdoDriver, $this->getQueryCache(), $this->getSchemaCache());
    }

    public function schemaCache(): SchemaCache
    {
        return $this->getSchemaCache();
    }

    public function prepareDatabase(ConnectionPDOInterface $db, string $fixture = __DIR__ . '/Fixture/mssql.sql'): void
    {
        $db->open();
        $lines = explode(';', file_get_contents($fixture));

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->getPDO()?->exec($line);
            }
        }
    }
}
