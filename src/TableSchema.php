<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\TableSchema as AbstractTableSchema;

/**
 * TableSchema represents the metadata of a database table.
 */
final class TableSchema extends AbstractTableSchema
{
    private ?string $catalogName = null;
    private array $foreignKeys = [];

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

    /**
     * @return array foreign keys of this table. Each array element is of the following structure:
     *
     * ```php
     * [
     *  'ForeignTableName',
     *  'fk1' => 'pk1',  // pk1 is in foreign table
     *  'fk2' => 'pk2',  // if composite foreign key
     * ]
     * ```
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function foreignKeys(array $value): void
    {
        $this->foreignKeys = $value;
    }
}
