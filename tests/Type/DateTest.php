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
* @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/date-transact-sql?view=sql-server-ver16
 */
final class DateTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(): void
    {
        $this->setFixture('Type/date.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('date_default');

        $this->assertSame('date', $tableSchema?->getColumn('Mydate')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydate')->getPhpType());
        $this->assertSame(
            '2007-05-08 12:35:29. 1234567 +12:15',
            $tableSchema?->getColumn('Mydate')->getDefaultValue(),
        );

        $this->assertSame('datetime', $tableSchema?->getColumn('Mydatetime')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime')->getPhpType());
        $this->assertSame('2007-05-08 12:35:29.123', $tableSchema?->getColumn('Mydatetime')->getDefaultValue());

        $this->assertSame('datetime2', $tableSchema?->getColumn('Mydatetime2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame(
            '2007-05-08 12:35:29. 1234567 +12:15',
            $tableSchema?->getColumn('Mydatetime2')->getDefaultValue(),
        );

        $this->assertSame('datetimeoffset', $tableSchema?->getColumn('Mydatetimeoffset')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetimeoffset')->getPhpType());
        $this->assertSame(
            '2007-05-08 12:35:29.1234567 +12:15',
            $tableSchema?->getColumn('Mydatetimeoffset')->getDefaultValue(),
        );

        $this->assertSame('time', $tableSchema?->getColumn('Mytime')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytime')->getPhpType());
        $this->assertSame(
            '2007-05-08 12:35:29. 1234567 +12:15',
            $tableSchema?->getColumn('Mytime')->getDefaultValue(),
        );

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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
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
