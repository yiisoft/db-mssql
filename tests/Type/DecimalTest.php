<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/decimal-and-numeric-transact-sql?view=sql-server-ver16
 */
final class DecimalTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('decimal_default');

        $this->assertSame('decimal', $tableSchema?->getColumn('Mydecimal')->getDbType());
        $this->assertSame(38, $tableSchema?->getColumn('Mydecimal')->getSize());
        $this->assertSame(9.9999999999999998e+037, $tableSchema?->getColumn('Mydecimal')->getDefaultValue());

        $db->createCommand()->insert('decimal_default', [])->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('decimal_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[decimal_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('decimal_default')->execute();
    }

    public function testDefaultValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/decimal.sql');

        $tableSchema = $db->getTableSchema('decimal_default');

        $this->assertSame('decimal', $tableSchema?->getColumn('Mydecimal')->getDbType());
        $this->assertSame(38, $tableSchema?->getColumn('Mydecimal')->getSize());
        $this->assertSame(9.9999999999999998e+037, $tableSchema?->getColumn('Mydecimal')->getDefaultValue());

        $db->createCommand()->dropTable('decimal_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/decimal.sql');

        $command = $db->createCommand();
        $command->insert('decimal_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[decimal_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('decimal_default')->execute();
    }

    /**
     * Max value is `99999999999999997748809823456034029568`.
     */
    public function testMaxValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/decimal.sql');

        $command = $db->createCommand();
        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('99999999999999997748809823456034029568'), 'Mydecimal2' => '0'],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydecimal1' => '99999999999999997748809823456034029568',
                'Mydecimal2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[decimal]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('99999999999999997748809823456034029568'), 'Mydecimal2' => null],
        )->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mydecimal1' => '99999999999999997748809823456034029568',
                'Mydecimal2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[decimal]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );
    }

    public function testMaxValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/decimal.sql');

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "[SQL Server]The number '199999999999999997748809823456034029570' is out of the range for numeric representation (maximum precision 38).",
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('199999999999999997748809823456034029570')],
        )->execute();
    }

    /**
     * Min value is `-99999999999999997748809823456034029569`.
     */
    public function testMinValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/decimal.sql');

        $command = $db->createCommand();
        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('-99999999999999997748809823456034029569'), 'Mydecimal2' => '0'],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydecimal1' => '-99999999999999997748809823456034029569',
                'Mydecimal2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[decimal]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('-99999999999999997748809823456034029569'), 'Mydecimal2' => null],
        )->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mydecimal1' => '-99999999999999997748809823456034029569',
                'Mydecimal2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[decimal]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );
    }

    public function testMinValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/decimal.sql');

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "[SQL Server]The number '199999999999999997748809823456034029570' is out of the range for numeric representation (maximum precision 38).",
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('-199999999999999997748809823456034029570')],
        )->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('decimal_default') !== null) {
            $command->dropTable('decimal_default')->execute();
        }

        $command->createTable(
            'decimal_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mydecimal' => 'DECIMAL(38, 0) DEFAULT 99999999999999997748809823456034029568', // Max value
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mydecimal' => '99999999999999997748809823456034029568',
        ];
    }
}
