<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class BitProvider
{
    public static function columns(): array
    {
        return [
            ['Mybit1', 'bit', 'boolean', false],
            ['Mybit2', 'bit', 'boolean', true],
        ];
    }
}
