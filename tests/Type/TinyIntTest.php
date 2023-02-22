<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Throwable;
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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
 */
final class TinyIntTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableDefaultValue(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('tinyint_default') !== null) {
            $command->dropTable('tinyint_default')->execute();
        }

        $command->createTable(
            'tinyint_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mytinyint' => 'TINYINT DEFAULT 255', // Max value is `255`.
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('tinyint_default');

        $this->assertSame('tinyint', $tableSchema?->getColumn('Mytinyint')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mytinyint')->getPhpType());
        $this->assertSame(255, $tableSchema?->getColumn('Mytinyint')->getDefaultValue());
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
        $this->setFixture('Type/tinyint.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('tinyint_default');

        $this->assertSame('tinyint', $tableSchema?->getColumn('Mytinyint')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mytinyint')->getPhpType());
        $this->assertSame(255, $tableSchema?->getColumn('Mytinyint')->getDefaultValue());

        $command = $db->createCommand();
        $command->insert('tinyint_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mytinyint' => '255',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM tinyint_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `255`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/tinyint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('tinyint', ['Mytinyint1' => 255, 'Mytinyint2' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mytinyint1' => '255',
                'Mytinyint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM tinyint WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('tinyint', ['Mytinyint1' => 255, 'Mytinyint2' => 0.5])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mytinyint1' => '255',
                'Mytinyint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM tinyint WHERE id = 2
                SQL
            )->queryOne()
        );

        $command->insert('tinyint', ['Mytinyint1' => 255, 'Mytinyint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '3',
                'Mytinyint1' => '255',
                'Mytinyint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM tinyint WHERE id = 3
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
        $this->setFixture('Type/tinyint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Arithmetic overflow'
        );

        $command->insert('tinyint', ['Mytinyint1' => 256])->execute();
    }

    /**
     * Min value is `0`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/tinyint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('tinyint', ['Mytinyint1' => 0, 'Mytinyint2' => null])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mytinyint1' => '0',
                'Mytinyint2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM tinyint WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('tinyint', ['Mytinyint1' => 0, 'Mytinyint2' => 0.9])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mytinyint1' => '0',
                'Mytinyint2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM tinyint WHERE id = 2
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
        $this->setFixture('Type/tinyint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Arithmetic overflow'
        );

        $command->insert('tinyint', ['Mytinyint1' => -1])->execute();
    }
}
