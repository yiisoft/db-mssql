<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class CharProvider
{
    public static function columns(): array
    {
        return [
            ['Mychar1', 'char(10)', 'string', 10, 'char'],
            ['Mychar2', 'char(1)', 'string', 1, 'c'],
        ];
    }
}
