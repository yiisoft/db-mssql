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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
 */
final class SmallIntTest extends TestCase
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

        $tableSchema = $db->getTableSchema('smallint_default');

        $this->assertSame('smallint', $tableSchema?->getColumn('Mysmallint')->getDbType());
        $this->assertSame('int', $tableSchema?->getColumn('Mysmallint')->getPhpType());
        $this->assertSame(32767, $tableSchema?->getColumn('Mysmallint')->getDefaultValue());

        $db->createCommand()->dropTable('smallint_default')->execute();
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
        $command->insert('smallint_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallint_default')->execute();
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
        $this->setFixture('Type/smallint.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('smallint_default');

        $this->assertSame('smallint', $tableSchema?->getColumn('Mysmallint')->getDbType());
        $this->assertSame('int', $tableSchema?->getColumn('Mysmallint')->getPhpType());
        $this->assertSame(32767, $tableSchema?->getColumn('Mysmallint')->getDefaultValue());

        $db->createCommand()->dropTable('smallint_default')->execute();
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
        $this->setFixture('Type/smallint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('smallint_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('smallint_default')->execute();
    }

    /**
     * Max value is `32767`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/smallint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('smallint', ['Mysmallint1' => 32767, 'Mysmallint2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mysmallint1' => '32767',
                'Mysmallint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $command->insert('smallint', ['Mysmallint1' => 32767, 'Mysmallint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mysmallint1' => '32767',
                'Mysmallint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint]] WHERE [[id]] = 2
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('smallint')->execute();
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
        $this->setFixture('Type/smallint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow'
        );

        $command->insert('smallint', ['Mysmallint1' => 32768])->execute();
    }

    /**
     * Min value is `-32768`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/smallint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('smallint', ['Mysmallint1' => -32768, 'Mysmallint2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mysmallint1' => '-32768',
                'Mysmallint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $command->insert('smallint', ['Mysmallint1' => -32768, 'Mysmallint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mysmallint1' => '-32768',
                'Mysmallint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[smallint]] WHERE [[id]] = 2
                SQL
            )->queryOne()
        );

        $db->createCommand()->dropTable('smallint')->execute();
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
        $this->setFixture('Type/smallint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[SQL Server]Arithmetic overflow'
        );

        $command->insert('smallint', ['Mysmallint1' => -32769])->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('smallint_default') !== null) {
            $command->dropTable('smallint_default')->execute();
        }

        $command->createTable(
            'smallint_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mysmallint' => 'SMALLINT DEFAULT 32767', // Max value is `32767`.
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Mysmallint' => '32767',
        ];
    }
}
