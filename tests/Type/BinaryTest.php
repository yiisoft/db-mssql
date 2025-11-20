<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/binary-and-varbinary-transact-sql?view=sql-server-ver16
 */
final class BinaryTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\BinaryProvider::columns
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        int $size,
        Expression $defaultValue,
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('binary_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('binary_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('binary_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [[id]], CONVERT(VARCHAR(100), [[Mybinary1]], 1) AS [[Mybinary1]], [[Mybinary2]] FROM [[binary_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('binary_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\BinaryProvider::columns
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        int $size,
        Expression $defaultValue,
    ): void {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BINARY);

        $tableSchema = $db->getTableSchema('binary_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('binary_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BINARY);

        $command = $db->createCommand();
        $command->insert('binary_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [[id]], CONVERT(VARCHAR(100), [[Mybinary1]], 1) AS [[Mybinary1]], [[Mybinary2]] FROM [[binary_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('binary_default')->execute();
    }

    /**
     * When the value is greater than the maximum value, the value is truncated.
     */
    public function testMaxValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BINARY);

        $command = $db->createCommand();
        $command->insert('binary', [
            'Mybinary1' => new Expression('CONVERT(binary(10), \'binary_default_value\')'),
            'Mybinary3' => new Expression('CONVERT(binary(1), \'bb\')'),
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybinary1' => '0x62696E6172795F646566',
                'Mybinary2' => null,
                'Mybinary3' => 'b',
                'Mybinary4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT [[id]], CONVERT(VARCHAR(100), [[Mybinary1]], 1) AS [[Mybinary1]], [[Mybinary2]], [[Mybinary3]], [[Mybinary4]] FROM [[binary]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('binary')->execute();
    }

    public function testValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_BINARY);

        $command = $db->createCommand();
        $command->insert('binary', [
            'Mybinary1' => new Expression('CONVERT(binary(10), \'binary\')'),
            'Mybinary2' => new Expression('CONVERT(binary(10), null)'),
            'Mybinary3' => new Expression('CONVERT(binary(1), \'b\')'),
            'Mybinary4' => new Expression('CONVERT(binary(1), null)'),
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybinary1' => '0x62696E61727900000000',
                'Mybinary2' => null,
                'Mybinary3' => 'b',
                'Mybinary4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT [[id]], CONVERT(VARCHAR(100), [[Mybinary1]], 1) AS [[Mybinary1]], [[Mybinary2]], [[Mybinary3]], [[Mybinary4]] FROM [[binary]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('binary')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('binary_default') !== null) {
            $command->dropTable('binary_default')->execute();
        }

        $command->createTable(
            'binary_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mybinary1' => 'BINARY(10) DEFAULT CONVERT(binary(10), \'binary\')',
                'Mybinary2' => 'BINARY(1) DEFAULT CONVERT(binary(1), \'b\')',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mybinary1' => '0x62696E61727900000000',
            'Mybinary2' => 'b',
        ];
    }
}
