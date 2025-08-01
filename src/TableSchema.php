<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

/**
 * Implements the MSSQL Server specific table schema.
 */
final class TableSchema extends \Yiisoft\Db\Schema\TableSchema
{
    public function __construct(
        string $name = '',
        string $schemaName = '',
        private string $catalogName = '',
        private string $serverName = '',
    ) {
        parent::__construct($name, $schemaName);
    }

    public function catalogName(string $catalogName): static
    {
        $this->catalogName = $catalogName;
        return $this;
    }

    public function getCatalogName(): string
    {
        return $this->catalogName;
    }

    public function getFullName(): string
    {
        $schemaName = $this->getSchemaName();
        $tableName = $this->getName();

        if ($schemaName === '') {
            return $tableName;
        }

        if ($this->catalogName === '') {
            return "$schemaName.$tableName";
        }

        if ($this->serverName === '') {
            return "$this->catalogName.$schemaName.$tableName";
        }

        return "$this->serverName.$this->catalogName.$schemaName.$tableName";
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function serverName(string $serverName): static
    {
        $this->serverName = $serverName;
        return $this;
    }
}
