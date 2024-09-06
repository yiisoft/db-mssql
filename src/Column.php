<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Builder\AbstractColumn;

/**
 * Provides a convenient way to create column schema for use with {@see Schema} for MSSQL Server.
 *
 * It has methods for specifying the properties of a column, such as its type, size, default value, and whether it
 * is nullable or not. It also provides a method for creating a column schema based on the specified properties.
 *
 * For example, the following code creates a column schema for an integer column:
 *
 * ```php
 * $column = (new Column(ColumnType::INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * Provides a fluent interface, which means that the methods can be chained together to create a column schema with
 * many properties in a single line of code.
 */
final class Column extends AbstractColumn
{
    /**
     * @return Expression|string|null The default value of the column.
     */
    public function getDefault(): Expression|string|null
    {
        if ($this->default instanceof Expression) {
            return $this->default;
        }

        return $this->buildDefaultValue();
    }
}
