<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function ceil;

final class ColumnDefinitionBuilder extends AbstractColumnDefinitionBuilder
{
    protected const AUTO_INCREMENT_KEYWORD = 'IDENTITY';

    protected const GENERATE_UUID_EXPRESSION = 'newid()';

    protected const TYPES_WITH_SIZE = [
        'decimal',
        'numeric',
        'float',
        'time',
        'datetime2',
        'datetimeoffset',
        'char',
        'varchar',
        'nchar',
        'nvarchar',
        'binary',
        'varbinary',
    ];

    protected const TYPES_WITH_SCALE = [
        'decimal',
        'numeric',
    ];

    public function build(ColumnSchemaInterface $column): string
    {
        return $this->buildType($column)
            . $this->buildAutoIncrement($column)
            . $this->buildPrimaryKey($column)
            . $this->buildUnique($column)
            . $this->buildNotNull($column)
            . $this->buildDefault($column)
            . $this->buildCheck($column)
            . $this->buildReferences($column)
            . $this->buildExtra($column);
    }

    protected function getDbType(ColumnSchemaInterface $column): string
    {
        /** @psalm-suppress DocblockTypeContradiction */
        return match ($column->getType()) {
            ColumnType::BOOLEAN => 'bit',
            ColumnType::BIT => match (true) {
                ($size = $column->getSize()) === null => 'bigint',
                $size === 1 => 'bit',
                $size <= 8 => 'tinyint',
                $size <= 16 => 'smallint',
                $size <= 32 => 'int',
                $size <= 64 => 'bigint',
                default => 'varbinary(' . ceil($size / 8) . ')',
            },
            ColumnType::TINYINT => 'tinyint',
            ColumnType::SMALLINT => 'smallint',
            ColumnType::INTEGER => 'int',
            ColumnType::BIGINT => 'bigint',
            ColumnType::FLOAT => 'real',
            ColumnType::DOUBLE => 'float(53)',
            ColumnType::DECIMAL => 'decimal',
            ColumnType::MONEY => 'money',
            ColumnType::CHAR => 'nchar',
            ColumnType::STRING => 'nvarchar',
            ColumnType::TEXT => 'nvarchar(max)',
            ColumnType::BINARY => 'varbinary(max)',
            ColumnType::UUID => 'uniqueidentifier',
            ColumnType::DATETIME => 'datetime2',
            ColumnType::TIMESTAMP => 'datetime2',
            ColumnType::DATE => 'date',
            ColumnType::TIME => 'time',
            ColumnType::ARRAY => 'json',
            ColumnType::STRUCTURED => 'json',
            ColumnType::JSON => 'json',
            default => 'varchar',
        };
    }
}
