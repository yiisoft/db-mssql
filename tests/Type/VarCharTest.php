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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/char-and-varchar-transact-sql?view=sql-server-ver16
 */
final class VarCharTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('varchar.sql');

        $db = $this->getConnection(true);

        $tableSchema = $db->getSchema()->getTableSchema('varchar_default');

        $this->assertSame('varchar(10)', $tableSchema->getColumn('Myvarchar1')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Myvarchar1')->getPhpType());
        $this->assertSame('varchar(100)', $tableSchema->getColumn('Myvarchar2')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Myvarchar2')->getPhpType());

        $command = $db->createCommand();
        $command->insert('varchar_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarchar1' => 'varchar',
                'Myvarchar2' => 'v',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM varchar_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testValue(): void
    {
        $this->setFixture('varchar.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert(
            'varchar',
            [
                'Myvarchar1' => '0123456789',
                'Myvarchar2' => null,
                'Myvarchar3' => str_repeat('b', 100),
                'Myvarchar4' => null,
            ],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarchar1' => '0123456789',
                'Myvarchar2' => null,
                'Myvarchar3' => str_repeat('b', 100),
                'Myvarchar4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM varchar WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testValueException(): void
    {
        $this->setFixture('varchar.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[22001]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]String or binary data would be truncated'
        );

        $command->insert('varchar', ['Myvarchar1' => '01234567891'])->execute();
    }
}
