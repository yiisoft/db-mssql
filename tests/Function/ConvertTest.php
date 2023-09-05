<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Function;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/es-es/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-ver16
 */
final class ConvertTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\ConvertionProvider::convertColumns
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
        string|ExpressionInterface $defaultValue
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('convert');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        if ($defaultValue instanceof ExpressionInterface) {
            $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());
        } else {
            $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());
        }

        $db->createCommand()->dropTable('convert')->execute();
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
        $command->insert('convert', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Myconvert1], [Myconvert2], [Myconvert3], [Myconvert4], [Myconvert5], CONVERT(VARCHAR(10), [Myconvert6], 1) [Myconvert6] FROM [convert] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('convert')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\ConvertionProvider::convertColumns
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
        string|ExpressionInterface $defaultValue
    ): void {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('convert');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        if ($defaultValue instanceof ExpressionInterface) {
            $this->assertEquals($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());
        } else {
            $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());
        }

        $db->createCommand()->dropTable('convert')->execute();
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
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $command->insert('convert', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Myconvert1], [Myconvert2], [Myconvert3], [Myconvert4], [Myconvert5], CONVERT(VARCHAR(10), [Myconvert6], 1) [Myconvert6] FROM [convert] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('convert')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('convert') !== null) {
            $command->dropTable('convert')->execute();
        }

        $command->createTable(
            'convert',
            [
                'id' => 'int IDENTITY(1,1) PRIMARY KEY',
                'Myconvert1' => 'INT NOT NULL DEFAULT CONVERT([INT],\'1\')',
                'Myconvert2' => 'INT NOT NULL DEFAULT CONVERT([INT],(14.85))',
                'Myconvert3' => 'FLOAT NOT NULL DEFAULT CONVERT([FLOAT],\'14.85\')',
                'Myconvert4' => 'VARCHAR(4) NOT NULL DEFAULT CONVERT([VARCHAR](4),(15.6))',
                'Myconvert5' => 'DATETIME NOT NULL DEFAULT CONVERT([DATETIME],\'2023-02-21\')',
                'Myconvert6' => 'BINARY(10) NOT NULL DEFAULT CONVERT([BINARY](10),\'testme\')',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myconvert1' => '1',
            'Myconvert2' => '14',
            'Myconvert3' => '14.85',
            'Myconvert4' => '15.6',
            'Myconvert5' => '2023-02-21 00:00:00.000',
            'Myconvert6' => '0x74657374',
        ];
    }
}
