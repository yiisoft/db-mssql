<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Mssql\Column\BinaryColumnSchema;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        $values = parent::buildingMethods();

        $values['binary()'][2] = BinaryColumnSchema::class;
        $values['binary(8)'][2] = BinaryColumnSchema::class;

        return $values;
    }
}
