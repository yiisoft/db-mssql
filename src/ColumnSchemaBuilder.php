<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\AbstractColumnSchemaBuilder;

final class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
{
    /**
     * Changes default format string to MSSQL ALTER COMMAND.
     */
    public function setAlterColumnFormat(): void
    {
        $this->format = '{type}{length}{notnull}{append}';
    }
}
