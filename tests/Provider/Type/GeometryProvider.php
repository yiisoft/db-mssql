<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Type;

final class GeometryProvider
{
    public static function columns(): array
    {
        return [
            ['Mygeometry1', 'geometry', 'string', '[geometry]::STGeomFromText(\'POINT(0 0)\',(0))'],
            ['Mygeometry2', 'geometry', 'string', null],
        ];
    }
}
