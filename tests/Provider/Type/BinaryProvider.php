<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

use Yiisoft\Db\Expression\Expression;

final class BinaryProvider
{
    public static function columns(): array
    {
        return [
            ['Mybinary1', 'binary', 'mixed', 10, new Expression("CONVERT([binary](10),'binary')")],
            ['Mybinary2', 'binary', 'mixed', 1, new Expression("CONVERT([binary](1),'b')")],
        ];
    }
}
