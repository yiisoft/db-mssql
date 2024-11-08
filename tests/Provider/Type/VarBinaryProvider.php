<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class VarBinaryProvider
{
    public static function columns(): array
    {
        return [
            ['Myvarbinary1', 'varbinary', 'mixed', 10, 'CONVERT([varbinary](10),\'varbinary\')'],
            ['Myvarbinary2', 'varbinary', 'mixed', 100, 'CONVERT([varbinary](100),\'v\')'],
            ['Myvarbinary3', 'varbinary', 'mixed', 20, 'hashbytes(\'MD5\',\'test string\')'],
        ];
    }
}
