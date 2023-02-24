<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class DateProvider
{
    public static function columns(): array
    {
        return [
            ['Mydate', 'date', 'string', '2007-05-08'],
            ['Mydatetime', 'datetime', 'string', '2007-05-08 12:35:29.123'],
            ['Mydatetime2', 'datetime2', 'string', '2007-05-08 12:35:29.1234567'],
            ['Mydatetimeoffset', 'datetimeoffset', 'string', '2007-05-08 12:35:29.1234567 +12:15'],
            ['Mytime', 'time', 'string', '12:35:29.1234567'],
        ];
    }
}
