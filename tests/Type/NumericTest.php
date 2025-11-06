<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
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
final class NumericTest extends TestCase
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

        $tableSchema = $db->getTableSchema('numeric_default');

        $this->assertSame('numeric', $tableSchema?->getColumn('Mynumeric')->getDbType());
        $this->assertSame(38, $tableSchema?->getColumn('Mynumeric')->getSize());
        $this->assertSame(9.9999999999999998e+037, $tableSchema?->getColumn('Mynumeric')->getDefaultValue());

        $db->createCommand()->dropTable('numeric_default')->execute();
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
        $command->insert('numeric_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[numeric_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('numeric_default')->execute();
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
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('numeric_default');

        $this->assertSame('numeric', $tableSchema?->getColumn('Mynumeric')->getDbType());
        $this->assertSame(38, $tableSchema?->getColumn('Mynumeric')->getSize());
        $this->assertSame(9.9999999999999998e+037, $tableSchema?->getColumn('Mynumeric')->getDefaultValue());

        $db->createCommand()->dropTable('numeric_default')->execute();
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
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('numeric_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[numeric_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('numeric_default')->execute();
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
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'numeric',
            ['Mynumeric1' => new Expression('99999999999999997748809823456034029568'), 'Mynumeric2' => 0],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mynumeric1' => '99999999999999997748809823456034029568',
                'Mynumeric2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[numeric]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert(
            'numeric',
            ['Mynumeric1' => new Expression('99999999999999997748809823456034029568'), 'Mynumeric2' => null],
        )->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mynumeric1' => '99999999999999997748809823456034029568',
                'Mynumeric2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[numeric]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('numeric')->execute();
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
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'numeric',
            ['Mynumeric1' => new Expression('-99999999999999997748809823456034029569'), 'Mynumeric2' => 0],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mynumeric1' => '-99999999999999997748809823456034029569',
                'Mynumeric2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[numeric]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert(
            'numeric',
            ['Mynumeric1' => new Expression('-99999999999999997748809823456034029569'), 'Mynumeric2' => null],
        )->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mynumeric1' => '-99999999999999997748809823456034029569',
                'Mynumeric2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[numeric]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('numeric')->execute();
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
        $this->setFixture('Type/numeric.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "[SQL Server]The number '199999999999999997748809823456034029570' is out of the range for numeric representation (maximum precision 38).",
        );

        $command->insert(
            'decimal',
            ['Mynumeric1' => new Expression('-199999999999999997748809823456034029570')],
        )->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('numeric_default') !== null) {
            $command->dropTable('numeric_default')->execute();
        }

        $command->createTable(
            'numeric_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mynumeric' => 'NUMERIC(38, 0) DEFAULT \'99999999999999997748809823456034029568\'', // Max value is `99999999999999997748809823456034029568`.
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mynumeric' => '99999999999999997748809823456034029568',
        ];
    }
}
