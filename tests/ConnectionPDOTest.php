<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionPDOTest;
use Yiisoft\Db\Tests\Support\DbHelper;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConnectionPDOTest extends CommonConnectionPDOTest
{
    use TestTrait;

    public function testCreateCommandWithLoggerProfiler(): void
    {
        $db = $this->getConnection();

        $db->setLogger(DbHelper::getLogger());
        $db->setProfiler(DbHelper::getProfiler());
        $command = $db->createCommand('SELECT 1');

        $this->assertSame('SELECT 1', $command->getSql());
        $this->assertSame([], $command->getParams());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function testGetLastInsertID(): void
    {
        $db = $this->getConnection();

        // One sequence, two tables
        $tableName1 = 'seqtable1';
        $tableName2 = 'seqtable2';
        $sequenceName = 'sequence1';

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema($tableName1) !== null) {
            $command->dropTable($tableName1)->execute();
        }

        if ($db->getSchema()->getTableSchema($tableName2) !== null) {
            $command->dropTable($tableName2)->execute();
        }

        $command->setSql(
            <<<SQL
            IF OBJECT_ID('$sequenceName', 'SO') IS NOT NULL DROP SEQUENCE $sequenceName
            SQL
        )->execute();

        $command->setSql(
            <<<SQL
            CREATE TABLE $tableName1 (seqnum INTEGER NOT NULL PRIMARY KEY, SomeNumber INT)
            SQL
        )->execute();
        $command->setSql(
            <<<SQL
            CREATE TABLE $tableName2 (ID INT IDENTITY(1, 2), SomeValue char(10))
            SQL
        )->execute();
        $command->setSql(
            <<<SQL
            CREATE SEQUENCE $sequenceName AS INTEGER START WITH 1 INCREMENT BY 1 MINVALUE 1 MAXVALUE 100 CYCLE
            SQL
        )->execute();
        $command->insert(
            $tableName1,
            ['seqnum' => new Expression("NEXT VALUE FOR $sequenceName"), 'SomeNumber' => 20],
        )->execute();
        $command->insert(
            $tableName1,
            ['seqnum' => new Expression("NEXT VALUE FOR $sequenceName"), 'SomeNumber' => 40],
        )->execute();
        $command->insert(
            $tableName1,
            ['seqnum' => new Expression("NEXT VALUE FOR $sequenceName"), 'SomeNumber' => 60],
        )->execute();
        $command->insert($tableName2, ['SomeValue' => 20])->execute();

        // Return the last inserted ID for the table with a sequence
        $this->assertSame('3', $db->getLastInsertId($sequenceName));

        // Return the last inserted ID for the table with an identity column
        $this->assertSame('1', $db->getLastInsertId());

        // Return empty sting
        $this->assertEmpty($db->getLastInsertId($tableName1));

        // Return empty sting
        $this->assertEmpty($db->getLastInsertId($tableName2));
    }
}
