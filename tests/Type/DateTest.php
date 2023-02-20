<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use DateTime;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
* @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/date-transact-sql?view=sql-server-ver16
 */
final class DateTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('date_default');

        $this->assertSame('date', $tableSchema->getColumn('Mydate')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mydate')->getPhpType());
        $this->assertSame('datetime', $tableSchema->getColumn('Mydatetime')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mydatetime')->getPhpType());
        $this->assertSame('datetime2', $tableSchema->getColumn('Mydatetime2')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame('datetimeoffset', $tableSchema->getColumn('Mydatetimeoffset')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mydatetimeoffset')->getPhpType());
        $this->assertSame('time', $tableSchema->getColumn('Mytime')->getDbType());
        $this->assertSame('string', $tableSchema->getColumn('Mytime')->getPhpType());

        $command = $db->createCommand();
        $command->insert('date_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydate' => '2007-05-08',
                'Mydatetime' => '2007-05-08 12:35:29.123',
                'Mydatetime2' => '2007-05-08 12:35:29.1234567',
                'Mydatetimeoffset' => '2007-05-08 12:35:29.1234567 +12:15',
                'Mytime' => '12:35:29.1234567',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM date_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testDefaultValueExpressions(): void
    {
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('date_default_expressions', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydate1' => date('Y-m-d'),
                'Mydate2' => date('Y-m-d'),
            ],
            $command->setSql(
                <<<SQL
                SELECT id, Mydate1, Mydate2 FROM date_default_expressions WHERE id = 1
                SQL
            )->queryOne()
        );
        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                $command->setSql(
                    <<<SQL
                    SELECT Mydatetime1 FROM date_default_expressions WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );
        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.uv P',
                $command->setSql(
                    <<<SQL
                    SELECT Mydatetimeoffset FROM date_default_expressions WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );
        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'H:i:s.uv',
                $command->setSql(
                    <<<SQL
                    SELECT Mytime FROM date_default_expressions WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );
    }

    public function testValue(): void
    {
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('date', [
            'Mydate1' => '2007-05-08',
            'Mydate2' => null,
            'Mydatetime1' => '2007-05-08 12:35:29.123',
            'Mydatetime2' => null,
            'Mydatetimeoffset1' => '2007-05-08 12:35:29.1234567 +12:15',
            'Mydatetimeoffset2' => null,
            'Mytime1' => '12:35:29.1234567',
            'Mytime2' => null,
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydate1' => '2007-05-08',
                'Mydate2' => null,
                'Mydatetime1' => '2007-05-08 12:35:29.123',
                'Mydatetime2' => null,
                'Mydatetimeoffset1' => '2007-05-08 12:35:29.1234567 +12:15',
                'Mydatetimeoffset2' => null,
                'Mytime1' => '12:35:29.1234567',
                'Mytime2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM date WHERE id = 1
                SQL
            )->queryOne(),
        );
    }

    public function testValueException(): void
    {
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'SQLSTATE[22007]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Conversion failed when converting date and/or time from character string.'
        );

        $db->createCommand()->insert('date', ['Mydate1' => '0000-00-00'])->execute();
    }
}
