<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

use DateTimeImmutable;

final class DateProvider
{
    public static function columns(): array
    {
        return [
            ['Mydate', 'date', 'DateTimeInterface', new DateTimeImmutable('2007-05-08')],
            ['Mydatetime', 'datetime', 'DateTimeInterface', new DateTimeImmutable('2007-05-08 12:35:29.123')],
            ['Mydatetime2', 'datetime2(7)', 'DateTimeInterface', new DateTimeImmutable('2007-05-08 12:35:29.1234567')],
            ['Mydatetimeoffset', 'datetimeoffset(7)', 'DateTimeInterface', new DateTimeImmutable('2007-05-08 12:35:29.1234567 +12:15')],
            ['Mytime', 'time(7)', 'DateTimeInterface', new DateTimeImmutable('12:35:29.1234567')],
        ];
    }
}
