<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

use DateTimeImmutable;

final class DateProvider
{
    public static function columns(): array
    {
        return [
            ['Mydate', 'date', DateTimeImmutable::class, new DateTimeImmutable('2007-05-08')],
            ['Mydatetime', 'datetime', DateTimeImmutable::class, new DateTimeImmutable('2007-05-08 12:35:29.123')],
            ['Mydatetime2', 'datetime2', DateTimeImmutable::class, new DateTimeImmutable('2007-05-08 12:35:29.123456')],
            ['Mydatetimeoffset', 'datetimeoffset', DateTimeImmutable::class, new DateTimeImmutable('2007-05-08 12:35:29.123456 +12:15')],
            ['Mytime', 'time', DateTimeImmutable::class, new DateTimeImmutable('12:35:29.123456')],
        ];
    }
}
