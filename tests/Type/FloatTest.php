<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/float-and-real-transact-sql?view=sql-server-ver16
 */
final class FloatTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('float_default');

        $this->assertSame('float', $tableSchema->getColumn('Myfloat')->getDbType());
        $this->assertSame('double', $tableSchema->getColumn('Myfloat')->getPhpType());

        $command = $db->createCommand();
        $command->insert('float_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myfloat' => '2.2300000000000001E-308',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `1.79E+308`.
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('float', ['Myfloat1' => '1.79E+308', 'Myfloat2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myfloat1' => '1.79E+308',
                'Myfloat2' => '0.0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('float', ['Myfloat1' => '1.79E+308', 'Myfloat2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myfloat1' => '1.79E+308',
                'Myfloat2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMaxValueException(): void
    {
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]The floating point value '1.80E+308' is out of the range of computer representation (8 bytes)."
        );

        $command->insert('float', ['Myfloat1' => new Expression('1.80E+308')])->execute();
    }

    /**
     * Min value is `-1.79E+308`.
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('float', ['Myfloat1' => '-1.79E+308', 'Myfloat2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myfloat1' => '-1.79E+308',
                'Myfloat2' => '0.0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('float', ['Myfloat1' => '-1.79E+308', 'Myfloat2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myfloat1' => '-1.79E+308',
                'Myfloat2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMinValueException(): void
    {
        $this->setFixture('Type/float.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]The floating point value '1.80E+308' is out of the range of computer representation (8 bytes)."
        );

        $command->insert('float', ['Myfloat1' => new Expression('-1.80E+308')])->execute();
    }
}
