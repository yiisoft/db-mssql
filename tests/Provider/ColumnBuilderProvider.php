<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Mssql\Column\BinaryColumnSchema;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        return [
            // building method, args, expected instance of, expected type, expected column method results
            ...parent::buildingMethods(),
            ['binary', [], BinaryColumnSchema::class, ColumnType::BINARY],
            ['binary', [8], BinaryColumnSchema::class, ColumnType::BINARY, ['getSize' => 8]],
        ];
    }
}
