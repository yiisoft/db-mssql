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
final class CharTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('Type/char.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('char_default');

        $this->assertSame('char(10)', $tableSchema->getColumn('Mychar1')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mychar1')->getPhpType());
        $this->assertSame('char(1)', $tableSchema->getColumn('Mychar2')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mychar2')->getPhpType());

        $command = $db->createCommand();
        $command->insert('char_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mychar1' => 'char      ',
                'Mychar2' => 'c',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM char_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testValue(): void
    {
        $this->setFixture('Type/char.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'char',
            [
                'Mychar1' => '0123456789',
                'Mychar2' => null,
                'Mychar3' => 'b',
                'Mychar4' => null,
            ],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mychar1' => '0123456789',
                'Mychar2' => null,
                'Mychar3' => 'b',
                'Mychar4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM char WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testValueException(): void
    {
        $this->setFixture('Type/char.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            '[ODBC Driver 17 for SQL Server][SQL Server]String or binary data would be truncated'
        );

        $command->insert('char', ['Mychar1' => '01234567891'])->execute();
    }
}
