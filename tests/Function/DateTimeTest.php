<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Function;

use DateTime;
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
 */
final class DateTimeTest extends TestCase
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

        if ($schema->getTableSchema('datetime') !== null) {
            $command->dropTable('datetime')->execute();
        }

        $command->createTable(
            'datetime',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Mydate1' => 'DATE NOT NULL DEFAULT GETUTCDATE()',
                'Mydate2' => 'DATE NOT NULL DEFAULT GETDATE()',
                'Mydate3' => 'DATE NOT NULL DEFAULT DATEADD(month, 1, \'2006-08-31\')',
                'Mydatetime1' => 'VARCHAR(10) NOT NULL DEFAULT CAST(datediff(day, \'2005-12-31\', \'2006-01-01\') AS varchar(10)) + \' days\'',
                'Mydatetime2' => 'VARCHAR(10) NOT NULL DEFAULT DATENAME(month,\'2023-02-21\')',
                'Mydatetime3' => 'INT NOT NULL DEFAULT DATEPART(month,\'2023-02-21\')',
                'Mydatetime4' => 'INT NOT NULL DEFAULT DAY(\'2023-02-21\')',
                'Mydatetime5' => 'INT NOT NULL DEFAULT MONTH(\'2023-02-21\')',
                'Mydatetime6' => 'INT NOT NULL DEFAULT YEAR(\'2023-02-21\')',
                'Mydatetime7' => 'DATETIME NOT NULL DEFAULT SYSDATETIME()',
                'Mydatetime8' => 'DATETIME NOT NULL DEFAULT SYSDATETIMEOFFSET()',
                'Mydatetimeoffset' => 'DATETIMEOFFSET NOT NULL DEFAULT SYSUTCDATETIME()',
                'Mytime' => 'TIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('datetime');

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/getutcdate-transact-sql?view=sql-server-ver16 */
        $this->assertSame('date', $tableSchema?->getColumn('Mydate1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydate1')->getPhpType());
        $this->assertSame(
            'getutcdate()',
            $tableSchema?->getColumn('Mydate1')->getDefaultValue(),
        );

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/getdate-transact-sql?view=sql-server-ver16 */
        $this->assertSame('date', $tableSchema?->getColumn('Mydate2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydate2')->getPhpType());
        $this->assertSame(
            'getdate()',
            $tableSchema?->getColumn('Mydate2')->getDefaultValue(),
        );

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/dateadd-transact-sql?view=sql-server-ver16 */
        $this->assertSame('date', $tableSchema?->getColumn('Mydate3')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydate3')->getPhpType());
        $this->assertSame(
            'dateadd(month,(1),\'2006-08-31\')',
            $tableSchema?->getColumn('Mydate3')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatetime1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime1')->getPhpType());
        $this->assertSame(
            'CONVERT([varchar](10),datediff(day,\'2005-12-31\',\'2006-01-01\'))+\' days\'',
            $tableSchema?->getColumn('Mydatetime1')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datename-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatetime2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame(
            'datename(month,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime2')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datepart-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime3')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime3')->getPhpType());
        $this->assertSame(
            'datepart(month,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime3')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/day-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime4')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime4')->getPhpType());
        $this->assertSame(
            'datepart(day,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime4')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/month-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime5')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime5')->getPhpType());
        $this->assertSame(
            'datepart(month,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime5')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/year-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime6')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime6')->getPhpType());
        $this->assertSame(
            'datepart(year,\'2023-02-21\')',
            $tableSchema?->getColumn('Mydatetime6')->getDefaultValue(),
        );

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/sysdatetime-transact-sql?view=sql-server-ver16 */
        $this->assertSame('datetime', $tableSchema?->getColumn('Mydatetime7')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime7')->getPhpType());
        $this->assertSame(
            'sysdatetime()',
            $tableSchema?->getColumn('Mydatetime7')->getDefaultValue(),
        );

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/sysdatetimeoffset-transact-sql?view=sql-server-ver16 */
        $this->assertSame('datetime', $tableSchema?->getColumn('Mydatetime8')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime8')->getPhpType());
        $this->assertSame(
            'sysdatetimeoffset()',
            $tableSchema?->getColumn('Mydatetime8')->getDefaultValue(),
        );

        /** https://docs.microsoft.com/en-us/sql/t-sql/functions/sysutcdatetime-transact-sql?view=sql-server-ver16 */
        $this->assertSame('time', $tableSchema?->getColumn('Mytime')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytime')->getPhpType());
        $this->assertSame('getdate()', $tableSchema?->getColumn('Mytime')->getDefaultValue());
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
        $this->setFixture('Function/datetime.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('datetime', [])->execute();
        $tableSchema = $db->getTableSchema('datetime');

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/getutcdate-transact-sql?view=sql-server-ver16 */
        $this->assertSame('date', $tableSchema?->getColumn('Mydate1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydate1')->getPhpType());
        $this->assertSame(
            'getutcdate()',
            $tableSchema?->getColumn('Mydate1')->getDefaultValue(),
        );

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/getdate-transact-sql?view=sql-server-ver16 */
        $this->assertSame('date', $tableSchema?->getColumn('Mydate2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydate2')->getPhpType());
        $this->assertSame(
            'getdate()',
            $tableSchema?->getColumn('Mydate2')->getDefaultValue(),
        );

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/dateadd-transact-sql?view=sql-server-ver16 */
        $this->assertSame('date', $tableSchema?->getColumn('Mydate3')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydate3')->getPhpType());
        $this->assertSame(
            'dateadd(month,(1),\'2006-08-31\')',
            $tableSchema?->getColumn('Mydate3')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatetime1')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame(
            "CONVERT([varchar](10),datediff(day,'2005-12-31','2006-01-01'))+' days'",
            $tableSchema?->getColumn('Mydatetime1')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatetime2')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime2')->getPhpType());
        $this->assertSame(
            "datename(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime2')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datepart-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime3')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime3')->getPhpType());
        $this->assertSame(
            "datepart(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime3')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/day-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime4')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime4')->getPhpType());
        $this->assertSame(
            "datepart(day,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime4')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/month-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime5')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime5')->getPhpType());
        $this->assertSame(
            "datepart(month,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime5')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/year-transact-sql?view=sql-server-ver16 */
        $this->assertSame('int', $tableSchema?->getColumn('Mydatetime6')->getDbType());
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatetime6')->getPhpType());
        $this->assertSame(
            "datepart(year,'2023-02-21')",
            $tableSchema?->getColumn('Mydatetime6')->getDefaultValue(),
        );

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/sysdatetime-transact-sql?view=sql-server-ver16 */
        $this->assertSame('datetime', $tableSchema?->getColumn('Mydatetime7')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime7')->getPhpType());
        $this->assertSame(
            'sysdatetime()',
            $tableSchema?->getColumn('Mydatetime7')->getDefaultValue(),
        );

        /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/sysdatetimeoffset-transact-sql?view=sql-server-ver16 */
        $this->assertSame('datetime', $tableSchema?->getColumn('Mydatetime8')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatetime8')->getPhpType());
        $this->assertSame(
            'sysdatetimeoffset()',
            $tableSchema?->getColumn('Mydatetime8')->getDefaultValue(),
        );

        /** https://docs.microsoft.com/en-us/sql/t-sql/functions/sysutcdatetime-transact-sql?view=sql-server-ver16 */
        $this->assertSame('time', $tableSchema?->getColumn('Mytime')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mytime')->getPhpType());
        $this->assertSame('getdate()', $tableSchema?->getColumn('Mytime')->getDefaultValue());

        $this->assertSame(
            [
                'id' => '1',
                'Mydate1' => date('Y-m-d'),
                'Mydate2' => date('Y-m-d'),
                'Mydate3' => '2006-09-30',
                'Mydatetime1' => '1 days',
                'Mydatetime2' => 'February',
                'Mydatetime3' => '2',
                'Mydatetime4' => '21',
                'Mydatetime5' => '2',
                'Mydatetime6' => '2023',
            ],
            $command->setSql(
                <<<SQL
                SELECT [id], [Mydate1], [Mydate2], [Mydate3], [Mydatetime1], [Mydatetime2], [Mydatetime3], [Mydatetime4], [Mydatetime5], [Mydatetime6] FROM [datetime] WHERE [id] = 1
                SQL
            )->queryOne(),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetime7] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetime8] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'Y-m-d H:i:s.uv P',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mydatetimeoffset] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );

        $this->assertInstanceOf(
            DateTime::class,
            DateTime::createFromFormat(
                'H:i:s.uv',
                (string) $command->setSql(
                    <<<SQL
                    SELECT [Mytime] FROM [datetime] WHERE id = 1
                    SQL,
                )->queryScalar(),
            ),
        );
    }
}
