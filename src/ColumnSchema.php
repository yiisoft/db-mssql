<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function bin2hex;
use function is_string;
use function str_starts_with;

/**
 * Represents the metadata of a column in a database table for MSSQL Server.
 *
 * It provides information about the column's name, type, size, precision, and other details.
 *
 * Is used to store and retrieve metadata about a column in a database table. It's typically used in conjunction with
 * the {@see TableSchema}, which represents the metadata of a database table as a whole.
 *
 * The following code shows how to use:
 *
 * ```php
 * use Yiisoft\Db\Mssql\ColumnSchema;
 *
 * $column = new ColumnSchema();
 * $column->name('id');
 * $column->allowNull(false);
 * $column->dbType('int');
 * $column->phpType('integer');
 * $column->type('integer');
 * $column->defaultValue(0);
 * $column->autoIncrement(true);
 * $column->primaryKey(true);
 * ```
 */
final class ColumnSchema extends AbstractColumnSchema
{
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getType() === SchemaInterface::TYPE_BINARY && $this->getDbType() === 'varbinary') {
            if ($value instanceof ParamInterface && is_string($value->getValue())) {
                $value = (string) $value->getValue();
            }
            if (is_string($value)) {
                return new Expression('CONVERT(VARBINARY(MAX), ' . ('0x' . bin2hex($value)) . ')');
            }
        }

        return parent::dbTypecast($value);
    }

    public function hasTimezone(): bool
    {
        return str_starts_with((string) $this->getDbType(), 'datetimeoffset');
    }
}
