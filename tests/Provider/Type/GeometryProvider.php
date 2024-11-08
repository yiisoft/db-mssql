<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

use Yiisoft\Db\Expression\Expression;

final class GeometryProvider
{
    public static function columns(): array
    {
        return [
            ['Mygeometry1', 'geometry', 'string', new Expression("[geometry]::STGeomFromText('POINT(0 0)',(0))")],
            ['Mygeometry2', 'geometry', 'string', null],
        ];
    }
}
