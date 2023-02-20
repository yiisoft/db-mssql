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
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/money-and-smallmoney-transact-sql?view=sql-server-ver16
 */
final class SmallMoneyTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('smallmoney_default');

        $this->assertSame('smallmoney', $tableSchema->getColumn('Mysmallmoney')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mysmallmoney')->getPhpType());

        $command = $db->createCommand();
        $command->insert('smallmoney_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mysmallmoney' => '214748.3647',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM smallmoney_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `214748.3647`.
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
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
                SELECT * FROM smallmoney WHERE id = 1
                SQL
            )->queryOne()
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
                SELECT * FROM smallmoney WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMaxValueException(): void
    {
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Arithmetic overflow error converting expression to data type smallmoney.'
        );

        $command->insert('smallmoney', ['Mysmallmoney1' => '214749.3647'])->execute();
    }

    /**
     * Min value is `-214748.3648`.
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
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
                SELECT * FROM smallmoney WHERE id = 1
                SQL
            )->queryOne()
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
                SELECT * FROM smallmoney WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    public function testMinValueException(): void
    {
        $this->setFixture('Type/smallmoney.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[22003]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Arithmetic overflow error converting expression to data type smallmoney.'
        );

        $command->insert('smallmoney', ['Mysmallmoney1' => '-214749.3648'])->execute();
    }
}
