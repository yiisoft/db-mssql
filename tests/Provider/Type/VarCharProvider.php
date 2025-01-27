<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

use Yiisoft\Db\Expression\Expression;

final class VarCharProvider
{
    public static function columns(): array
    {
        return [
            ['Myvarchar1', 'varchar', 'string', 10, 'varchar'],
            ['Myvarchar2', 'varchar', 'string', 100, 'v'],
            ['Myvarchar3', 'varchar', 'string', 20, new Expression('TRY_CAST(datepart(year,getdate()) AS [varchar](20))')],
        ];
    }
}
