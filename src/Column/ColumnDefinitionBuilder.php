<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function ceil;
use function strtoupper;

final class ColumnDefinitionBuilder extends AbstractColumnDefinitionBuilder
{
    protected const AUTO_INCREMENT_KEYWORD = 'IDENTITY';

    protected const TYPES_WITH_SIZE = [
        'decimal',
        'numeric',
        'float',
        'datetime2',
        'datetimeoffset',
        'time',
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

    public function build(ColumnInterface $column): string
    {
        return $this->buildType($column)
            . $this->buildAutoIncrement($column)
            . $this->buildPrimaryKey($column)
            . $this->buildUnique($column)
            . $this->buildNotNull($column)
            . $this->buildDefault($column)
            . $this->buildCheck($column)
            . $this->buildCollate($column)
            . $this->buildReferences($column)
            . $this->buildExtra($column);
    }

    public function buildAlter(ColumnInterface $column): string
    {
        return $this->buildType($column)
            . $this->buildNotNull($column)
            . $this->buildExtra($column);
    }

    protected function buildCheck(ColumnInterface $column): string
    {
        $check = $column->getCheck();

        if (empty($check)) {
            $name = $column->getName();

            if (empty($name)) {
                return '';
            }

            return match ($column->getType()) {
                ColumnType::ARRAY, ColumnType::STRUCTURED, ColumnType::JSON =>
                    ' CHECK (isjson(' . $this->queryBuilder->getQuoter()->quoteSimpleColumnName($name) . ') > 0)',
                default => '',
            };
        }

        return " CHECK ($check)";
    }

    protected function buildOnDelete(string $onDelete): string
    {
        if (strtoupper($onDelete) === ReferentialAction::RESTRICT) {
            return '';
        }

        return " ON DELETE $onDelete";
    }

    protected function buildOnUpdate(string $onUpdate): string
    {
        if (strtoupper($onUpdate) === ReferentialAction::RESTRICT) {
            return '';
        }

        return " ON UPDATE $onUpdate";
    }

    protected function getDbType(ColumnInterface $column): string
    {
        $size = $column->getSize();

        /** @psalm-suppress DocblockTypeContradiction */
        $dbType = $column->getDbType() ?? match ($column->getType()) {
            ColumnType::BOOLEAN => 'bit',
            ColumnType::BIT => match (true) {
                $size === null => 'bigint',
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
            ColumnType::STRING => 'nvarchar(' . (($size ?? '255') ?: 'max') . ')',
            ColumnType::TEXT => 'nvarchar(max)',
            ColumnType::BINARY => 'varbinary(max)',
            ColumnType::UUID => 'uniqueidentifier',
            ColumnType::TIMESTAMP => 'datetime2',
            ColumnType::DATETIME => 'datetime2',
            ColumnType::DATETIMETZ => 'datetimeoffset',
            ColumnType::TIME => 'time',
            ColumnType::TIMETZ => 'time',
            ColumnType::DATE => 'date',
            ColumnType::ARRAY => 'nvarchar(max)',
            ColumnType::STRUCTURED => 'nvarchar(max)',
            ColumnType::JSON => 'nvarchar(max)',
            default => 'nvarchar',
        };

        return match ($dbType) {
            'timestamp' => 'datetime2',
            'varchar' => 'varchar(' . ($size ?: 'max') . ')',
            'nvarchar' => 'nvarchar(' . ($size ?: 'max') . ')',
            'varbinary' => 'varbinary(' . ($size ?: 'max') . ')',
            default => $dbType,
        };
    }

    protected function getDefaultUuidExpression(): string
    {
        return 'newid()';
    }
}
