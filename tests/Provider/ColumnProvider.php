<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Mssql\Column\BinaryColumn;

class ColumnProvider extends \Yiisoft\Db\Tests\Provider\ColumnProvider
{
    public static function predefinedTypes(): array
    {
        $values = parent::predefinedTypes();
        $values['binary'][0] = BinaryColumn::class;

        return $values;
    }

    public static function dbTypecastColumns(): array
    {
        $values = parent::dbTypecastColumns();
        $values['binary'][0] = new BinaryColumn();

        return $values;
    }
}
