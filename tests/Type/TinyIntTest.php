<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mssql\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function dirname;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
 */
final class TinyIntTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('tinyint_default');

        $this->assertSame('tinyint', $tableSchema?->getColumn('Mytinyint')->getDbType());
        $this->assertSame(255, $tableSchema?->getColumn('Mytinyint')->getDefaultValue());

        $db->createCommand()->dropTable('tinyint_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('tinyint_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[tinyint_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('tinyint_default')->execute();
    }

    public function testDefaultValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_TINYINT);

        $tableSchema = $db->getTableSchema('tinyint_default');

        $this->assertSame('tinyint', $tableSchema?->getColumn('Mytinyint')->getDbType());
        $this->assertSame(255, $tableSchema?->getColumn('Mytinyint')->getDefaultValue());

        $db->createCommand()->dropTable('tinyint_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_TINYINT);

        $command = $db->createCommand();
        $command->insert('tinyint_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[tinyint_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('tinyint_default')->execute();
    }

    /**
     * Max value is `255`.
     */
    public function testMaxValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_TINYINT);

        $command = $db->createCommand();
        $command->insert('tinyint', ['Mytinyint1' => 255, 'Mytinyint2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mytinyint1' => '255',
                'Mytinyint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[tinyint]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('tinyint', ['Mytinyint1' => 255, 'Mytinyint2' => 0.5])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mytinyint1' => '255',
                'Mytinyint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[tinyint]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );

        $command->insert('tinyint', ['Mytinyint1' => 255, 'Mytinyint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '3',
                'Mytinyint1' => '255',
                'Mytinyint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[tinyint]] WHERE [[id]] = 3
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('tinyint')->execute();
    }

    public function testMaxValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_TINYINT);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow',
        );

        $command->insert('tinyint', ['Mytinyint1' => 256])->execute();
    }

    /**
     * Min value is `0`.
     */
    public function testMinValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_TINYINT);

        $command = $db->createCommand();
        $command->insert('tinyint', ['Mytinyint1' => 0, 'Mytinyint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mytinyint1' => '0',
                'Mytinyint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[tinyint]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('tinyint', ['Mytinyint1' => 0, 'Mytinyint2' => 0.9])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mytinyint1' => '0',
                'Mytinyint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[tinyint]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('tinyint')->execute();
    }

    public function testMinValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_TINYINT);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow',
        );

        $command->insert('tinyint', ['Mytinyint1' => -1])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('tinyint_default') !== null) {
            $command->dropTable('tinyint_default')->execute();
        }

        $command->createTable(
            'tinyint_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mytinyint' => 'TINYINT DEFAULT 255', // Max value is `255`.
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mytinyint' => '255',
        ];
    }
}
