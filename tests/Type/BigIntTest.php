<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
 */
final class BigIntTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('bigint_default');

        $this->assertSame('bigint', $tableSchema?->getColumn('Mybigint')->getDbType());
        $this->assertSame(9_223_372_036_854_775_807, $tableSchema?->getColumn('Mybigint')->getDefaultValue());

        $db->createCommand()->dropTable('bigint_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('bigint_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[bigint_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('bigint_default')->execute();
    }

    public function testDefaultValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BIGINT);

        $tableSchema = $db->getTableSchema('bigint_default');

        $this->assertSame('bigint', $tableSchema?->getColumn('Mybigint')->getDbType());
        $this->assertSame(9_223_372_036_854_775_807, $tableSchema?->getColumn('Mybigint')->getDefaultValue());

        $db->createCommand()->dropTable('bigint_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BIGINT);

        $command = $db->createCommand();
        $command->insert('bigint_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[bigint_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('bigint_default')->execute();
    }

    /**
     * Min value is `-9223372036854775808`, but when the value is less than `-9223372036854775808` it is out of range
     * and save as `-9223372036854775808`.
     */
    public function testMinValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BIGINT);

        $command = $db->createCommand();
        $command->insert('bigint', ['Mybigint1' => '-9223372036854775808', 'Mybigint2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybigint1' => '-9223372036854775808',
                'Mybigint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bigint WHERE id = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('bigint', ['Mybigint1' => '-9223372036854775809', 'Mybigint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybigint1' => '-9223372036854775808',
                'Mybigint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bigint WHERE id = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('bigint')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('bigint_default') !== null) {
            $command->dropTable('bigint_default')->execute();
        }

        $command->createTable(
            'bigint_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mybigint' => 'BIGINT DEFAULT 9223372036854775807', // Max value
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mybigint' => '9223372036854775807',
        ];
    }
}
