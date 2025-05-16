<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Mssql\Column\BinaryColumn;
use Yiisoft\Db\Mssql\Column\DateTimeColumn;

class ColumnProvider extends \Yiisoft\Db\Tests\Provider\ColumnProvider
{
    public static function predefinedTypes(): array
    {
        $values = parent::predefinedTypes();
        $values['binary'][0] = BinaryColumn::class;
        $values['datetime'][0] = DateTimeColumn::class;

        return $values;
    }

    public static function dbTypecastColumns(): array
    {
        $values = parent::dbTypecastColumns();
        $values['binary'][0] = new BinaryColumn();
        $values['timestamp'][0] = new DateTimeColumn(ColumnType::TIMESTAMP, size: 0);
        $values['timestamp6'][0] = new DateTimeColumn(ColumnType::TIMESTAMP, size: 6);
        $values['datetime'][0] = new DateTimeColumn(size: 0);
        $values['datetime6'][0] = new DateTimeColumn(size: 6);
        $values['datetimetz'][0] = new DateTimeColumn(ColumnType::DATETIMETZ, size: 0);
        $values['datetimetz6'][0] = new DateTimeColumn(ColumnType::DATETIMETZ, size: 6);
        $values['time'][0] = new DateTimeColumn(ColumnType::TIME, size: 0);
        $values['time6'][0] = new DateTimeColumn(ColumnType::TIME, size: 6);
        $values['timetz'][0] = new DateTimeColumn(ColumnType::TIMETZ, size: 0);
        $values['timetz6'][0] = new DateTimeColumn(ColumnType::TIMETZ, size: 6);
        $values['date'][0] = new DateTimeColumn(ColumnType::DATE);

        return $values;
    }

    public static function phpTypecastColumns(): array
    {
        $values = parent::phpTypecastColumns();
        $values['binary'][0] = new BinaryColumn();
        $values['timestamp'][0] = new DateTimeColumn(ColumnType::TIMESTAMP);
        $values['datetime'][0] = new DateTimeColumn();
        $values['datetimetz'][0] = new DateTimeColumn(ColumnType::DATETIMETZ);
        $values['time'][0] = new DateTimeColumn(ColumnType::TIME);
        $values['timetz'][0] = new DateTimeColumn(ColumnType::TIMETZ);
        $values['date'][0] = new DateTimeColumn(ColumnType::DATE);

        return $values;
    }
}
