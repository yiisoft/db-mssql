<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class CharProvider
{
    public static function columns(): array
    {
        return [
            ['Mychar1', 'char', 'string', 10, 'char'],
            ['Mychar2', 'char', 'string', 1, 'c'],
        ];
    }
}
