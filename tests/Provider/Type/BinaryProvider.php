<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class BinaryProvider
{
    public static function columns(): array
    {
        return [
            ['Mybinary1', 'binary(10)', 'mixed', 10, 'CONVERT([binary](10),\'binary\')'],
            ['Mybinary2', 'binary(1)', 'mixed', 1, 'CONVERT([binary](1),\'b\')'],
        ];
    }
}
