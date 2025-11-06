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
final class SmallMoneyTest extends TestCase
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

        $tableSchema = $db->getTableSchema('smallmoney_default');

        $this->assertSame('smallmoney', $tableSchema?->getColumn('Mysmallmoney')->getDbType());
        $this->assertSame('214748.3647', $tableSchema?->getColumn('Mysmallmoney')->getDefaultValue());

        $db->createCommand()->dropTable('smallmoney_default')->execute();
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
        $command->insert('smallmoney_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallmoney_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallmoney_default')->execute();
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
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('smallmoney_default');

        $this->assertSame('smallmoney', $tableSchema?->getColumn('Mysmallmoney')->getDbType());
        $this->assertSame('214748.3647', $tableSchema?->getColumn('Mysmallmoney')->getDefaultValue());

        $db->createCommand()->dropTable('smallmoney_default')->execute();
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
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('smallmoney_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallmoney_default]]
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallmoney_default')->execute();
    }

    /**
     * Max value is `214748.3647`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('smallmoney', ['Mysmallmoney1' => '214748.3647', 'Mysmallmoney2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mysmallmoney1' => '214748.3647',
                'Mysmallmoney2' => '.0000',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallmoney]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('smallmoney', ['Mysmallmoney1' => '214748.3647', 'Mysmallmoney2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mysmallmoney1' => '214748.3647',
                'Mysmallmoney2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallmoney]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallmoney')->execute();
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
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow error converting expression to data type smallmoney.',
        );

        $command->insert('smallmoney', ['Mysmallmoney1' => '214749.3647'])->execute();
    }

    /**
     * Min value is `-214748.3648`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('smallmoney', ['Mysmallmoney1' => '-214748.3648', 'Mysmallmoney2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mysmallmoney1' => '-214748.3648',
                'Mysmallmoney2' => '.0000',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallmoney]] WHERE [[id]] = 1
                SQL,
            )->queryOne(),
        );

        $command->insert('smallmoney', ['Mysmallmoney1' => '-214748.3648', 'Mysmallmoney2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mysmallmoney1' => '-214748.3648',
                'Mysmallmoney2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallmoney]] WHERE [[id]] = 2
                SQL,
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallmoney')->execute();
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
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow error converting expression to data type smallmoney.',
        );

        $command->insert('smallmoney', ['Mysmallmoney1' => '-214749.3648'])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('smallmoney_default') !== null) {
            $command->dropTable('smallmoney_default')->execute();
        }

        $command->createTable(
            'smallmoney_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mysmallmoney' => 'SMALLMONEY DEFAULT \'214748.3647\'', // Max value is `214748.3647`.
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mysmallmoney' => '214748.3647',
        ];
    }
}
