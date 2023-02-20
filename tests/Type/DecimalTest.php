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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/decimal-and-numeric-transact-sql?view=sql-server-ver16
 */
final class DecimalTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('decimal_default');

        $this->assertSame('decimal', $tableSchema->getColumn('Mydecimal')->getDbType());
        $this->assertSame('double', $tableSchema->getColumn('Mydecimal')->getPhpType());

        $command = $db->createCommand();
        $command->insert('decimal_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydecimal' => '99999999999999997748809823456034029568',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM decimal_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `99999999999999997748809823456034029568`.
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('99999999999999997748809823456034029568'), 'Mydecimal2' => '0']
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydecimal1' => '99999999999999997748809823456034029568',
                'Mydecimal2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM decimal WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('99999999999999997748809823456034029568'), 'Mydecimal2' => null]
        )->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mydecimal1' => '99999999999999997748809823456034029568',
                'Mydecimal2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM decimal WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMaxValueException(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]The number '199999999999999997748809823456034029570' is out of the range for numeric representation (maximum precision 38)."
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('199999999999999997748809823456034029570')],
        )->execute();
    }

    /**
     * Min value is `-99999999999999997748809823456034029569`.
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('-99999999999999997748809823456034029569'), 'Mydecimal2' => '0'],
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydecimal1' => '-99999999999999997748809823456034029569',
                'Mydecimal2' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM decimal WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('-99999999999999997748809823456034029569'), 'Mydecimal2' => null]
        )->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mydecimal1' => '-99999999999999997748809823456034029569',
                'Mydecimal2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM decimal WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMinValueException(): void
    {
        $this->setFixture('Type/decimal.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]The number '199999999999999997748809823456034029570' is out of the range for numeric representation (maximum precision 38)."
        );

        $command->insert(
            'decimal',
            ['Mydecimal1' => new Expression('-199999999999999997748809823456034029570')],
        )->execute();
    }
}
