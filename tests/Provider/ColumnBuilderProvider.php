<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Mssql\Column\BinaryColumn;
use Yiisoft\Db\Mssql\Column\DateTimeColumn;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        $values = parent::buildingMethods();

        $values['binary()'][2] = BinaryColumn::class;
        $values['binary(8)'][2] = BinaryColumn::class;
        $values['timestamp()'][2] = DateTimeColumn::class;
        $values['timestamp(3)'][2] = DateTimeColumn::class;
        $values['datetime()'][2] = DateTimeColumn::class;
        $values['datetime(3)'][2] = DateTimeColumn::class;
        $values['datetimeWithTimezone()'][2] = DateTimeColumn::class;
        $values['datetimeWithTimezone(3)'][2] = DateTimeColumn::class;
        $values['time()'][2] = DateTimeColumn::class;
        $values['time(3)'][2] = DateTimeColumn::class;
        $values['timeWithTimezone()'][2] = DateTimeColumn::class;
        $values['timeWithTimezone(3)'][2] = DateTimeColumn::class;
        $values['date()'][2] = DateTimeColumn::class;

        return $values;
    }
}
