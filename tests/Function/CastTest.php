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
 * @link https://learn.microsoft.com/es-es/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-ver16
 */
final class CastTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\ConvertionProvider::castColumns
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

        $tableSchema = $db->getTableSchema('cast');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('cast')->execute();
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
        $command->insert('cast', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Mycast1], [Mycast2], [Mycast3], [Mycast4], [Mycast5], CONVERT(VARCHAR(10), [Mycast6], 1) [Mycast6] FROM [cast] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('cast')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\ConvertionProvider::castColumns
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
        $tableSchema = $db->getTableSchema('cast');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('cast')->execute();
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
        $command->insert('cast', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Mycast1], [Mycast2], [Mycast3], [Mycast4], [Mycast5], CONVERT(VARCHAR(10), [Mycast6], 1) [Mycast6] FROM [cast] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('cast')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('cast') !== null) {
            $command->dropTable('cast')->execute();
        }

        $command->createTable(
            'cast',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mycast1' => 'INT NOT NULL DEFAULT CAST(\'1\' AS INT)',
                'Mycast2' => 'INT NOT NULL DEFAULT CAST(14.85 AS INT)',
                'Mycast3' => 'FLOAT NOT NULL DEFAULT CAST(\'14.85\' AS FLOAT)',
                'Mycast4' => 'VARCHAR(4) NOT NULL DEFAULT CAST(15.6 AS VARCHAR(4))',
                'Mycast5' => 'DATETIME NOT NULL DEFAULT CAST(\'2023-02-21\' AS DATETIME)',
                'Mycast6' => 'BINARY(10) NOT NULL DEFAULT CAST(\'testme\' AS BINARY(10))',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mycast1' => '1',
            'Mycast2' => '14',
            'Mycast3' => '14.85',
            'Mycast4' => '15.6',
            'Mycast5' => '2023-02-21 00:00:00.000',
            'Mycast6' => '0x74657374',
        ];
    }
}
