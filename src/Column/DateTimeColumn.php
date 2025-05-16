<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

final class DateTimeColumn extends \Yiisoft\Db\Schema\Column\DateTimeColumn
{
    protected function getMillisecondsFormat(): string
    {
        return match ($this->getDbType()) {
            'smalldatetime' => '',
            'datetime' => '.v',
            default => parent::getMillisecondsFormat(),
        };
    }
}
