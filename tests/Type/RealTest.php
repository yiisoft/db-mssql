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
final class RealTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('real.sql');

        $db = $this->getConnection(true);

        $tableSchema = $db->getSchema()->getTableSchema('real_default');

        $this->assertSame('real', $tableSchema->getColumn('Myreal')->getDbType());
        $this->assertSame('double', $tableSchema->getColumn('Myreal')->getPhpType());

        $command = $db->createCommand();
        $command->insert('real_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myreal' => '3.4E+38',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `3.4E+38`.
     */
    public function testMaxValue(): void
    {
        $this->setFixture('real.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('real', ['Myreal1' => '3.4E+38', 'Myreal2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myreal1' => '3.4E+38',
                'Myreal2' => '0.0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('real', ['Myreal1' => '3.4E+38', 'Myreal2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myreal1' => '3.4E+38',
                'Myreal2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMaxValueException(): void
    {
        $this->setFixture('float.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Arithmetic overflow error for type real"
        );

        $command->insert('real', ['Myreal1' => new Expression('4.4E+38')])->execute();
    }

    /**
     * Min value is `-3.40E+38`.
     */
    public function testMinValue(): void
    {
        $this->setFixture('real.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('real', ['Myreal1' => '-3.4E+38', 'Myreal2' => '0'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myreal1' => '-3.4E+38',
                'Myreal2' => '0.0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('real', ['Myreal1' => '-3.4E+38', 'Myreal2' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myreal1' => '-3.4E+38',
                'Myreal2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM real WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMinValueException(): void
    {
        $this->setFixture('real.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Arithmetic overflow error for type real"
        );

        $command->insert('real', ['Myreal1' => new Expression('-4.4E+38')])->execute();
    }
}
