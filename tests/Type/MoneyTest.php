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
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/money-and-smallmoney-transact-sql?view=sql-server-ver16
 */
final class MoneyTest extends TestCase
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

        $tableSchema = $db->getTableSchema('money_default');

        $this->assertSame('money', $tableSchema?->getColumn('Mymoney')->getDbType());
        $this->assertSame('922337203685477.5807', $tableSchema?->getColumn('Mymoney')->getDefaultValue());

        $db->createCommand()->dropTable('money_default')->execute();
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
        $command->insert('money_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[money_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('money_default')->execute();
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
        $this->setFixture('Type/money.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('money_default');

        $this->assertSame('money', $tableSchema->getColumn('Mymoney')->getDbType());
        $this->assertSame('922337203685477.5807', $tableSchema->getColumn('Mymoney')->getDefaultValue());

        $db->createCommand()->dropTable('money_default')->execute();
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
        $this->setFixture('Type/money.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('money_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[money_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('money_default')->execute();
    }

    /**
     * Max value is `922337203685477.5807`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/money.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('money', ['Mymoney1' => '922337203685477.5807', 'Mymoney2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mymoney1' => '922337203685477.5807',
                'Mymoney2' => '.0000',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM money WHERE id = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('money', ['Mymoney1' => '922337203685477.5807', 'Mymoney2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mymoney1' => '922337203685477.5807',
                'Mymoney2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM money WHERE id = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('money')->execute();
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
        $this->setFixture('Type/money.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow error converting expression to data type money.',
        );

        $command->insert('money', ['Mymoney1' => '922337203685478.5808'])->execute();
    }

    /**
     * Min value is `-922337203685477.5808`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/money.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('money', ['Mymoney1' => '-922337203685477.5808', 'Mymoney2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mymoney1' => '-922337203685477.5808',
                'Mymoney2' => '.0000',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM money WHERE id = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('money', ['Mymoney1' => '-922337203685477.5808', 'Mymoney2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mymoney1' => '-922337203685477.5808',
                'Mymoney2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM money WHERE id = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('money')->execute();
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
        $this->setFixture('Type/money.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow error converting expression to data type money.',
        );

        $command->insert('money', ['Mymoney1' => '-922337203685480.5808'])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('money_default') !== null) {
            $command->dropTable('money_default')->execute();
        }

        $command->createTable(
            'money_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mymoney' => 'MONEY DEFAULT \'922337203685477.5807\'', // Max value is `922337203685477.5807`.
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mymoney' => '922337203685477.5807',
        ];
    }
}
