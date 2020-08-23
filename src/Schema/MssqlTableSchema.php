<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Schema;

use Yiisoft\Db\Schema\TableSchema as AbstractTableSchema;

/**
 * TableSchema represents the metadata of a database table.
 */
final class MssqlTableSchema extends AbstractTableSchema
{
    private ?string $catalogName = null;

    /**
     * @param string|null name of the catalog (database) that this table belongs to. Defaults to null, meaning no
     * catalog (or the current database).
     */
    public function catalogName(?string $value): void
    {
        $this->catalogName = $value;
    }

    public function getCatalogName(): ?string
    {
        return $this->catalogName;
    }

    public function primaryKeys(array $value): void
    {

    }
}
