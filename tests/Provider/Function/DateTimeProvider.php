<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Function;

use Yiisoft\Db\Expression\Expression;

final class DateTimeProvider
{
    public static function columns(): array
    {
        return [
            /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/getutcdate-transact-sql?view=sql-server-ver16 */
            ['Mydate1', 'date', 'DateTimeInterface', new Expression('getutcdate()')],

            /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/getdate-transact-sql?view=sql-server-ver16 */
            ['Mydate2', 'date', 'DateTimeInterface', new Expression('getdate()')],

            /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/dateadd-transact-sql?view=sql-server-ver16 */
            ['Mydate3', 'date', 'DateTimeInterface', new Expression('dateadd(month,(1),\'2006-08-31\')')],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datediff-transact-sql?view=sql-server-ver16 */
            [
                'Mydatetime1',
                'varchar(10)',
                'string',
                'CONVERT([varchar](10),datediff(day,\'2005-12-31\',\'2006-01-01\'))+\' days\'',
            ],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datename-transact-sql?view=sql-server-ver16 */
            ['Mydatetime2', 'varchar(10)', 'string', 'datename(month,\'2023-02-21\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/datepart-transact-sql?view=sql-server-ver16 */
            ['Mydatetime3', 'int', 'integer', 'datepart(month,\'2023-02-21\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/day-transact-sql?view=sql-server-ver16 */
            ['Mydatetime4', 'int', 'integer', 'datepart(day,\'2023-02-21\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/month-transact-sql?view=sql-server-ver16 */
            ['Mydatetime5', 'int', 'integer', 'datepart(month,\'2023-02-21\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/year-transact-sql?view=sql-server-ver16 */
            ['Mydatetime6', 'int', 'integer', 'datepart(year,\'2023-02-21\')'],

            /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/sysdatetime-transact-sql?view=sql-server-ver16 */
            ['Mydatetime7', 'datetime', 'DateTimeInterface', new Expression('sysdatetime()')],

            /** @link https://docs.microsoft.com/en-us/sql/t-sql/functions/sysdatetimeoffset-transact-sql?view=sql-server-ver16 */
            ['Mydatetimeoffset1', 'datetimeoffset(7)', 'DateTimeInterface', new Expression('sysdatetimeoffset()')],

            /** https://docs.microsoft.com/en-us/sql/t-sql/functions/sysutcdatetime-transact-sql?view=sql-server-ver16 */
            ['Mydatetimeoffset2', 'datetimeoffset(7)', 'DateTimeInterface', new Expression('sysutcdatetime()')],
        ];
    }
}
