<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function hex2bin;
use function str_starts_with;
use function substr;

final class ColumnFactory extends AbstractColumnFactory
{
    /**
     * Mapping from physical column types (keys) to abstract column types (values).
     *
     * @var string[]
     * @psalm-var array<string, ColumnType::*>
     */
    protected const TYPE_MAP = [
        /** Exact numbers */
        'bit' => ColumnType::BOOLEAN,
        'tinyint' => ColumnType::TINYINT,
        'smallint' => ColumnType::SMALLINT,
        'int' => ColumnType::INTEGER,
        'bigint' => ColumnType::BIGINT,
        'numeric' => ColumnType::DECIMAL,
        'decimal' => ColumnType::DECIMAL,
        'smallmoney' => ColumnType::MONEY,
        'money' => ColumnType::MONEY,

        /** Approximate numbers */
        'float' => ColumnType::FLOAT,
        'real' => ColumnType::FLOAT,
        'double' => ColumnType::DOUBLE,

        /** Date and time */
        'smalldatetime' => ColumnType::DATETIME,
        'datetime' => ColumnType::DATETIME,
        'datetime2' => ColumnType::DATETIME,
        'datetimeoffset' => ColumnType::DATETIMETZ,
        'time' => ColumnType::TIME,
        'date' => ColumnType::DATE,

        /** Character strings */
        'char' => ColumnType::CHAR,
        'varchar' => ColumnType::STRING,
        'text' => ColumnType::TEXT,

        /** Unicode character strings */
        'nchar' => ColumnType::CHAR,
        'nvarchar' => ColumnType::STRING,
        'ntext' => ColumnType::TEXT,

        /** Binary strings */
        'binary' => ColumnType::BINARY,
        'varbinary' => ColumnType::BINARY,
        'image' => ColumnType::BINARY,

        /**
         * Other data types 'cursor' type can't be used with tables
         */
        'timestamp' => ColumnType::BINARY,
        'hierarchyid' => ColumnType::STRING,
        'uniqueidentifier' => ColumnType::UUID,
        'sql_variant' => ColumnType::STRING,
        'xml' => ColumnType::STRING,
        'table' => ColumnType::STRING,
    ];

    public function fromPseudoType(string $pseudoType, array $info = []): ColumnInterface
    {
        if ($pseudoType === PseudoType::UUID_PK_SEQ && !isset($info['defaultValue'])) {
            $info['defaultValue'] = new Expression('newsequentialid()');
        }

        return parent::fromPseudoType($pseudoType, $info);
    }

    protected function getColumnClass(string $type, array $info = []): string
    {
        return match ($type) {
            ColumnType::BINARY => BinaryColumn::class,
            ColumnType::TIMESTAMP => DateTimeColumn::class,
            ColumnType::DATETIME => DateTimeColumn::class,
            ColumnType::DATETIMETZ => DateTimeColumn::class,
            ColumnType::TIME => DateTimeColumn::class,
            ColumnType::TIMETZ => DateTimeColumn::class,
            ColumnType::DATE => DateTimeColumn::class,
            default => parent::getColumnClass($type, $info),
        };
    }

    protected function getType(string $dbType, array $info = []): string
    {
        if (isset($info['check'], $info['name']) && str_starts_with($info['check'], "(isjson([{$info['name']}])")) {
            return ColumnType::JSON;
        }

        return parent::getType($dbType, $info);
    }

    protected function normalizeNotNullDefaultValue(string $defaultValue, ColumnInterface $column): mixed
    {
        if ($defaultValue[0] === '(' && $defaultValue[-1] === ')') {
            $defaultValue = substr($defaultValue, 1, -1);
        }

        if (str_starts_with($defaultValue, '0x')) {
            return hex2bin(substr($defaultValue, 2));
        }

        return parent::normalizeNotNullDefaultValue($defaultValue, $column);
    }
}
