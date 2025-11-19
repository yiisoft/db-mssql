<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function str_repeat;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/binary-and-varbinary-transact-sql?view=sql-server-ver16
 */
final class VarBinaryTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\VarBinaryProvider::columns
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        int $size,
        Expression $defaultValue,
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('varbinary_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('varbinary_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('varbinary_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [[id]], [[Myvarbinary1]], [[Myvarbinary2]], CONVERT(VARCHAR(20), [[Myvarbinary3]], 1) AS [[Myvarbinary3]] FROM [[varbinary_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('varbinary_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\VarBinaryProvider::columns
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        int $size,
        Expression $defaultValue,
    ): void {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/varbinary.sql');

        $tableSchema = $db->getTableSchema('varbinary_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('varbinary_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/varbinary.sql');

        $command = $db->createCommand();
        $command->insert('varbinary_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [[id]], [[Myvarbinary1]], [[Myvarbinary2]], CONVERT(VARCHAR(20), [[Myvarbinary3]], 1) AS [[Myvarbinary3]] FROM [[varbinary_default]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('varbinary_default')->execute();
    }

    /**
     * When the value is greater than the maximum value, the value is truncated.
     */
    public function testMaxValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(dirname(__DIR__) . '/Support/Fixture/Type/varbinary.sql');

        $command = $db->createCommand();
        $command->insert('varbinary', [
            'Myvarbinary1' => new Expression('CONVERT(varbinary(10), \'binary_default_value\')'),
            'Myvarbinary3' => new Expression('CONVERT(binary(100), \'' . str_repeat('v', 101) . '\')'),
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarbinary1' => 'binary_def',
                'Myvarbinary2' => null,
                'Myvarbinary3' => str_repeat('v', 100),
                'Myvarbinary4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[varbinary]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('varbinary')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('varbinary_default') !== null) {
            $command->dropTable('varbinary_default')->execute();
        }

        $command->createTable(
            'varbinary_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myvarbinary1' => 'VARBINARY(10) DEFAULT CONVERT(varbinary(10), \'varbinary\')',
                'Myvarbinary2' => 'VARBINARY(100) DEFAULT CONVERT(varbinary(100), \'v\')',
                'Myvarbinary3' => 'VARBINARY(20) DEFAULT hashbytes(\'MD5\',\'test string\')',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myvarbinary1' => 'varbinary',
            'Myvarbinary2' => 'v',
            'Myvarbinary3' => '0x6F8DB599DE986FAB7A',
        ];
    }
}
