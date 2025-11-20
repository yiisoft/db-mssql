<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mssql\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/money-and-smallmoney-transact-sql?view=sql-server-ver16
 */
final class SmallMoneyTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('smallmoney_default');

        $this->assertSame('smallmoney', $tableSchema?->getColumn('Mysmallmoney')->getDbType());
        $this->assertSame('214748.3647', $tableSchema?->getColumn('Mysmallmoney')->getDefaultValue());

        $db->createCommand()->dropTable('smallmoney_default')->execute();
    }

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

    public function testDefaultValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLMONEY);

        $tableSchema = $db->getTableSchema('smallmoney_default');

        $this->assertSame('smallmoney', $tableSchema?->getColumn('Mysmallmoney')->getDbType());
        $this->assertSame('214748.3647', $tableSchema?->getColumn('Mysmallmoney')->getDefaultValue());

        $db->createCommand()->dropTable('smallmoney_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLMONEY);

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
     */
    public function testMaxValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLMONEY);

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

    public function testMaxValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLMONEY);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow error converting expression to data type smallmoney.',
        );

        $command->insert('smallmoney', ['Mysmallmoney1' => '214749.3647'])->execute();
    }

    /**
     * Min value is `-214748.3648`.
     */
    public function testMinValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLMONEY);

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

    public function testMinValueException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture(FixtureDump::TYPE_SMALLMONEY);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow error converting expression to data type smallmoney.',
        );

        $command->insert('smallmoney', ['Mysmallmoney1' => '-214749.3648'])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getSharedConnection();

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
