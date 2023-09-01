<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Mssql\BinaryColumnSchema;

class ColumnSchemaProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaProvider
{
    public static function predefinedTypes(): array
    {
        $values = parent::predefinedTypes();
        $values['binary'][0] = BinaryColumnSchema::class;

        return $values;
    }

    public static function dbTypecastColumns(): array
    {
        $values = parent::dbTypecastColumns();
        $values['binary'][0] = BinaryColumnSchema::class;

        return $values;
    }
}
