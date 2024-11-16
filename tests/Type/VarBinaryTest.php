<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

use function str_repeat;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/binary-and-varbinary-transact-sql?view=sql-server-ver16
 */
final class VarBinaryTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\VarBinaryProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        int $size,
        Expression $defaultValue
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('varbinary_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('varbinary_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
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
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('varbinary_default')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Type\VarBinaryProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        int $size,
        Expression $defaultValue
    ): void {
        $this->setFixture('Type/varbinary.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('varbinary_default');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($size, $tableSchema?->getColumn($column)->getSize());
        $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('varbinary_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValueWithInsert(): void
    {
        $this->setFixture('Type/varbinary.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('varbinary_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [[id]], [[Myvarbinary1]], [[Myvarbinary2]], CONVERT(VARCHAR(20), [[Myvarbinary3]], 1) AS [[Myvarbinary3]] FROM [[varbinary_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('varbinary_default')->execute();
    }

    /**
     * When the value is greater than the maximum value, the value is truncated.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/varbinary.sql');

        $db = $this->getConnection(true);
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
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('varbinary')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testValue(): void
    {
        $this->setFixture('Type/binary.sql');

        $db = $this->getConnection(true);
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
                SELECT id, CONVERT(VARCHAR(100), [[Mybinary1]], 1) AS [[Mybinary1]], [[Mybinary2]], [[Mybinary3]], [[Mybinary4]] FROM [[binary]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('binary')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

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
