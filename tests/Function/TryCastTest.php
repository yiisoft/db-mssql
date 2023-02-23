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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/functions/try-cast-transact-sql?view=sql-server-ver16
 */
final class TryCastTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\ConvertionProvider::tryCastColumns
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

        $tableSchema = $db->getTableSchema('trycast');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('trycast')->execute();
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
        $command->insert('trycast', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Mytrycast1], [Mytrycast2], [Mytrycast3], [Mytrycast4], [Mytrycast5], CONVERT(VARCHAR(10), [Mytrycast6], 1) [Mytrycast] FROM [trycast] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('trycast')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\ConvertionProvider::tryCastColumns
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
        $tableSchema = $db->getTableSchema('trycast');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('trycast')->execute();
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
        $command->insert('trycast', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT [id], [Mytrycast1], [Mytrycast2], [Mytrycast3], [Mytrycast4], [Mytrycast5], CONVERT(VARCHAR(10), [Mytrycast6], 1) [Mytrycast] FROM [trycast] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('trycast')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('trycast') !== null) {
            $command->dropTable('trycast')->execute();
        }

        $command->createTable(
            'trycast',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mytrycast1' => 'INT NOT NULL DEFAULT TRY_CAST(\'1\' AS INT)',
                'Mytrycast2' => 'INT NOT NULL DEFAULT TRY_CAST((14.85) AS INT)',
                'Mytrycast3' => 'FLOAT NOT NULL DEFAULT TRY_CAST(\'14.85\' AS FLOAT)',
                'Mytrycast4' => 'VARCHAR(4) NOT NULL DEFAULT TRY_CAST((15.6) AS VARCHAR(4))',
                'Mytrycast5' => 'DATETIME NOT NULL DEFAULT TRY_CAST(\'2023-02-21\' AS DATETIME)',
                'Mytrycast6' => 'BINARY(10) NOT NULL DEFAULT TRY_CAST(\'testme\' AS BINARY(10))',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mytrycast1' => '1',
            'Mytrycast2' => '14',
            'Mytrycast3' => '14.85',
            'Mytrycast4' => '15.6',
            'Mytrycast5' => '2023-02-21 00:00:00.000',
            'Mytrycast' => '0x74657374',
        ];
    }
}
