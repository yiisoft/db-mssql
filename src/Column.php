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
 * $column = (new Column(SchemaInterface::TYPE_INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * Provides a fluent interface, which means that the methods can be chained together to create a column schema with
 * many properties in a single line of code.
 */
final class Column extends AbstractColumn
{
    private bool $clustered = false;

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

    public function clustered(): self
    {
        $this->format = '{type}{length}{notnull}{primarykey}{unique}{default}{check}{comment}{append}{clustered}';
        $this->clustered = true;
        return $this;
    }

    public function isClustered(): self
    {
        $this->clustered = true;
        return $this;
    }

    /**
     * Returns the complete column definition from input format.
     *
     * @param string $format The format of the definition.
     *
     * @return string A string containing the complete column definition.
     */
    protected function buildCompleteString(string $format): string
    {
        $placeholderValues = [
            '{type}' => $this->type,
            '{length}' => $this->buildLengthString(),
            '{unsigned}' => $this->buildUnsignedString(),
            '{notnull}' => $this->buildNotNullString(),
            '{primarykey}' => $this->buildPrimaryKeyString(),
            '{unique}' => $this->buildUniqueString(),
            '{default}' => $this->buildDefaultString(),
            '{check}' => $this->buildCheckString(),
            '{comment}' => $this->buildCommentString(),
            '{append}' => $this->buildAppendString(),
            '{clustered}' => $this->buildClustered(),
        ];

        return strtr($format, $placeholderValues);
    }

    private function buildClustered(): string
    {
        return $this->clustered ? ' CLUSTERED' : '';
    }
}
