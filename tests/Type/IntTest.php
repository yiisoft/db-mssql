<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
 */
final class IntTest extends TestCase
{
    use TestTrait;

    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('int_default');

        $this->assertSame('int', $tableSchema?->getColumn('Myint')->getDbType());
        $this->assertSame(2_147_483_647, $tableSchema?->getColumn('Myint')->getDefaultValue());

        $db->createCommand()->dropTable('int_default')->execute();
    }

    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('int_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[int_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('int_default')->execute();
    }

    public function testDefaultValue(): void
    {
        $this->setFixture('Type/int.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('int_default');

        $this->assertSame('int', $tableSchema?->getColumn('Myint')->getDbType());
        $this->assertSame(2_147_483_647, $tableSchema?->getColumn('Myint')->getDefaultValue());

        $db->createCommand()->dropTable('int_default')->execute();
    }

    public function testDefaultValueWithInsert(): void
    {
        $this->setFixture('Type/int.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('int_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[int_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('int_default')->execute();
    }

    /**
     * Max value is `2147483647`.
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/int.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('int', ['Myint1' => 2_147_483_647, 'Myint2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myint1' => '2147483647',
                'Myint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[int]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $command->insert('int', ['Myint1' => 2_147_483_647, 'Myint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myint1' => '2147483647',
                'Myint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[int]] WHERE [[id]]= 2
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('int')->execute();
    }

    public function testMaxValueException(): void
    {
        $this->setFixture('Type/int.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow'
        );

        $command->insert('int', ['Myint1' => 2_147_483_648])->execute();
    }

    /**
     * Min value is `-2147483648`.
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/int.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('int', ['Myint1' => -2_147_483_648, 'Myint2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myint1' => '-2147483648',
                'Myint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[int]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $command->insert('int', ['Myint1' => -2_147_483_648, 'Myint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myint1' => '-2147483648',
                'Myint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[int]] WHERE [[id]] = 2
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('int')->execute();
    }

    public function testMinValueException(): void
    {
        $this->setFixture('Type/int.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow'
        );

        $command->insert('int', ['Myint1' => -2_147_483_649])->execute();
    }

    public function testIdentityTypecasting(): void
    {
        $this->setFixture('Type/int.sql');
        $db = $this->getConnection(true);
        $db->createCommand()->insert('int', ['Myint1' => 1])->execute();

        $result = $db
            ->select('id')
            ->from('int')
            ->withTypecasting()
            ->where(['id' => 1])
            ->one();

        $this->assertSame(1, $result['id']);
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('int_default') !== null) {
            $command->dropTable('int_default')->execute();
        }

        $command->createTable(
            'int_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myint' => 'INT DEFAULT 2147483647', // Max value is `2147483647`.
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myint' => '2147483647',
        ];
    }
}
