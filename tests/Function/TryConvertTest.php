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
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/functions/try-convert-transact-sql?view=sql-server-ver16
 */
final class TryConvertTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\ConvertionProvider::tryConvertColumns
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
        string $defaultValue
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('tryconvert');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('tryconvert')->execute();
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
        $command->insert('tryconvert', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Mytryconvert1], [Mytryconvert2], [Mytryconvert3], [Mytryconvert4], [Mytryconvert5], CONVERT(VARCHAR(10), [Mytryconvert6], 1) [Mytryconvert6] FROM [tryconvert] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('tryconvert')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\ConvertionProvider::tryConvertColumns
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
        string $defaultValue
    ): void {
        $this->setFixture('Function/convertion.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('tryconvert');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('tryconvert')->execute();
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
        $command->insert('tryconvert', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Mytryconvert1], [Mytryconvert2], [Mytryconvert3], [Mytryconvert4], [Mytryconvert5], CONVERT(VARCHAR(10), [Mytryconvert6], 1) [Mytryconvert6] FROM [tryconvert] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('tryconvert')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('tryconvert') !== null) {
            $command->dropTable('tryconvert')->execute();
        }

        $command->createTable(
            'tryconvert',
            [
                'id' => 'INT NOT NULL IDENTITY',
                'Mytryconvert1' => 'INT NOT NULL DEFAULT TRY_CONVERT(INT, \'1\')',
                'Mytryconvert2' => 'INT NOT NULL DEFAULT TRY_CONVERT(INT, 14.85)',
                'Mytryconvert3' => 'FLOAT NOT NULL DEFAULT TRY_CONVERT(FLOAT, \'14.85\')',
                'Mytryconvert4' => 'VARCHAR(4) NOT NULL DEFAULT TRY_CONVERT(VARCHAR(4), 15.6)',
                'Mytryconvert5' => 'DATETIME NOT NULL DEFAULT TRY_CONVERT(DATETIME, \'2023-02-21\')',
                'Mytryconvert6' => 'BINARY(10) NOT NULL DEFAULT TRY_CONVERT(BINARY(10), \'testme\')',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mytryconvert1' => '1',
            'Mytryconvert2' => '14',
            'Mytryconvert3' => '14.85',
            'Mytryconvert4' => '15.6',
            'Mytryconvert5' => '2023-02-21 00:00:00.000',
            'Mytryconvert6' => '0x74657374',
        ];
    }
}
