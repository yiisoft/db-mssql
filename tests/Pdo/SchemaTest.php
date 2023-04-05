<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Pdo;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Schema;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Support\DbHelper;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SchemaTest extends \PHPUnit\Framework\TestCase
{
    use TestTrait;

    public function testNotConnectionPDO(): void
    {
        $db = $this->createMock(\Yiisoft\Db\Connection\ConnectionInterface::class);
        $schema = new Schema($db, DbHelper::getSchemaCache());

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Only PDO connections are supported.');

        $schema->refreshTableSchema('customer');
    }
}
