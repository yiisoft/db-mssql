<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class BitProvider
{
    public static function columns(): array
    {
        return [
            ['Mybit1', 'bit', 'integer', 0],
            ['Mybit2', 'bit', 'integer', 1],
            ['Mybit3', 'bit', 'integer', 2],
        ];
    }
}
