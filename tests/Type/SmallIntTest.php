<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;
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

    public function testDefaultValue(): void
    {
        $this->setFixture('smallint.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('smallint_default');

        $this->assertSame('smallint', $tableSchema->getColumn('Mysmallint')->getDbType());
        $this->assertSame('integer', $tableSchema->getColumn('Mysmallint')->getPhpType());

        $command = $db->createCommand();
        $command->insert('smallint_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mysmallint' => '32767',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM smallint_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `32767`.
     */
    public function testMaxValue(): void
    {
        $this->setFixture('smallint.sql');

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
                SELECT * FROM smallint WHERE id = 1
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
                SELECT * FROM smallint WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMaxValueException(): void
    {
        $this->setFixture('smallint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Arithmetic overflow'
        );

        $command->insert('smallint', ['Mysmallint1' => 32768])->execute();
    }

    /**
     * Min value is `-32768`.
     */
    public function testMinValue(): void
    {
        $this->setFixture('smallint.sql');

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
                SELECT * FROM smallint WHERE id = 1
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
                SELECT * FROM smallint WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMinValueException(): void
    {
        $this->setFixture('smallint.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Arithmetic overflow'
        );

        $command->insert('smallint', ['Mysmallint1' => -32769])->execute();
    }
}
