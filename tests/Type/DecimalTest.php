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

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/decimal-and-numeric-transact-sql?view=sql-server-ver16
 */
final class DecimalTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('decimal_default');

        $this->assertSame('decimal(38,0)', $tableSchema?->getColumn('Mydecimal')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Mydecimal')->getPhpType());
        $this->assertSame(38, $tableSchema?->getColumn('Mydecimal')->getSize());
        $this->assertSame(9.9999999999999998e+037, $tableSchema?->getColumn('Mydecimal')->getDefaultValue());

        $db->createCommand()->insert('decimal_default', [])->execute();
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
        $command->insert('decimal_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[decimal_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('decimal_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('decimal_default');

        $this->assertSame('decimal(38,0)', $tableSchema?->getColumn('Mydecimal')->getDbType());
        $this->assertSame('double', $tableSchema?->getColumn('Mydecimal')->getPhpType());
        $this->assertSame(38, $tableSchema?->getColumn('Mydecimal')->getSize());
        $this->assertSame(9.9999999999999998e+037, $tableSchema?->getColumn('Mydecimal')->getDefaultValue());

        $db->createCommand()->dropTable('decimal_default')->execute();
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
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('decimal_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[decimal_default]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('decimal_default')->execute();
    }

    /**
     * Max value is `99999999999999997748809823456034029568`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('99999999999999997748809823456034029568'), 'Mydecimal2' => '0']
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
                SQL
            )->queryOne()
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('99999999999999997748809823456034029568'), 'Mydecimal2' => null]
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
                SQL
            )->queryOne()
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValueException(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]The number '199999999999999997748809823456034029570' is out of the range for numeric representation (maximum precision 38)."
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('199999999999999997748809823456034029570')],
        )->execute();
    }

    /**
     * Min value is `-99999999999999997748809823456034029569`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
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
                SQL
            )->queryOne()
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('-99999999999999997748809823456034029569'), 'Mydecimal2' => null]
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
                SQL
            )->queryOne()
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValueException(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]The number '199999999999999997748809823456034029570' is out of the range for numeric representation (maximum precision 38)."
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('-199999999999999997748809823456034029570')],
        )->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

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
