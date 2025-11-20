<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mssql\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
 */
final class SmallIntTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('smallint_default');

        $this->assertSame('smallint', $tableSchema?->getColumn('Mysmallint')->getDbType());
        $this->assertSame(32767, $tableSchema?->getColumn('Mysmallint')->getDefaultValue());

        $db->createCommand()->dropTable('smallint_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('smallint_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallint_default')->execute();
    }

    public function testDefaultValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLINT);

        $tableSchema = $db->getTableSchema('smallint_default');

        $this->assertSame('smallint', $tableSchema?->getColumn('Mysmallint')->getDbType());
        $this->assertSame(32767, $tableSchema?->getColumn('Mysmallint')->getDefaultValue());

        $db->createCommand()->dropTable('smallint_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLINT);

        $command = $db->createCommand();
        $command->insert('smallint_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallint_default')->execute();
    }

    /**
     * Max value is `32767`.
     */
    public function testMaxValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLINT);

        $command = $db->createCommand();
        $command->insert('smallint', ['Mysmallint1' => 32767, 'Mysmallint2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mysmallint1' => '32767',
                'Mysmallint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('smallint', ['Mysmallint1' => 32767, 'Mysmallint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mysmallint1' => '32767',
                'Mysmallint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallint')->execute();
    }

    public function testMaxValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLINT);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow',
        );

        $command->insert('smallint', ['Mysmallint1' => 32768])->execute();
    }

    /**
     * Min value is `-32768`.
     */
    public function testMinValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLINT);

        $command = $db->createCommand();
        $command->insert('smallint', ['Mysmallint1' => -32768, 'Mysmallint2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mysmallint1' => '-32768',
                'Mysmallint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('smallint', ['Mysmallint1' => -32768, 'Mysmallint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mysmallint1' => '-32768',
                'Mysmallint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallint')->execute();
    }

    public function testMinValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLINT);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow',
        );

        $command->insert('smallint', ['Mysmallint1' => -32769])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('smallint_default') !== null) {
            $command->dropTable('smallint_default')->execute();
        }

        $command->createTable(
            'smallint_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mysmallint' => 'SMALLINT DEFAULT 32767', // Max value is `32767`.
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mysmallint' => '32767',
        ];
    }
}
