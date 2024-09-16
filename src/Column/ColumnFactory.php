<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

final class ColumnFactory extends AbstractColumnFactory
{
    /**
     * Mapping from physical column types (keys) to abstract column types (values).
     *
     * @var string[]
     */
    private const TYPE_MAP = [
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
        'date' => ColumnType::DATE,
        'time' => ColumnType::TIME,
        'smalldatetime' => ColumnType::DATETIME,
        'datetime' => ColumnType::DATETIME,
        'datetime2' => ColumnType::DATETIME,
        'datetimeoffset' => ColumnType::DATETIME,

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
        'timestamp' => ColumnType::TIMESTAMP,
        'hierarchyid' => ColumnType::STRING,
        'uniqueidentifier' => ColumnType::STRING,
        'sql_variant' => ColumnType::STRING,
        'xml' => ColumnType::STRING,
        'table' => ColumnType::STRING,
    ];

    protected function getType(string $dbType, array $info = []): string
    {
        return self::TYPE_MAP[$dbType] ?? ColumnType::STRING;
    }

    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        if ($type === ColumnType::BINARY) {
            return (new BinaryColumnSchema($type))->load($info);
        }

        return parent::fromType($type, $info);
    }

    protected function isDbType(string $dbType): bool
    {
        return isset(self::TYPE_MAP[$dbType]);
    }
}
