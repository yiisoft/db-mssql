<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class BitProvider
{
    public static function columns(): array
    {
        return [
            ['Mybit1', 'bit', 'bool', false],
            ['Mybit2', 'bit', 'bool', true],
        ];
    }
}
