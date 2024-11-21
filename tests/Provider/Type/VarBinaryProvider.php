<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

use Yiisoft\Db\Expression\Expression;

final class VarBinaryProvider
{
    public static function columns(): array
    {
        return [
            ['Myvarbinary1', 'varbinary', 'mixed', 10, new Expression("CONVERT([varbinary](10),'varbinary')")],
            ['Myvarbinary2', 'varbinary', 'mixed', 100, new Expression("CONVERT([varbinary](100),'v')")],
            ['Myvarbinary3', 'varbinary', 'mixed', 20, new Expression("hashbytes('MD5','test string')")],
        ];
    }
}
