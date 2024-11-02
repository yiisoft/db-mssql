<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class VarCharProvider
{
    public static function columns(): array
    {
        return [
            ['Myvarchar1', 'varchar', 'string', 10, 'varchar'],
            ['Myvarchar2', 'varchar', 'string', 100, 'v'],
            ['Myvarchar3', 'varchar', 'string', 20, 'TRY_CAST(datepart(year,getdate()) AS [varchar](20))'],
        ];
    }
}
