<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class ColumnFactory extends AbstractColumnFactory
{
    /**
     * Mapping from physical column types (keys) to abstract column types (values).
     *
     * @var string[]
     */
    private const TYPE_MAP = [
        /** Exact numbers */
        'bit' => SchemaInterface::TYPE_BOOLEAN,
        'tinyint' => SchemaInterface::TYPE_TINYINT,
        'smallint' => SchemaInterface::TYPE_SMALLINT,
        'int' => SchemaInterface::TYPE_INTEGER,
        'bigint' => SchemaInterface::TYPE_BIGINT,
        'numeric' => SchemaInterface::TYPE_DECIMAL,
        'decimal' => SchemaInterface::TYPE_DECIMAL,
        'smallmoney' => SchemaInterface::TYPE_MONEY,
        'money' => SchemaInterface::TYPE_MONEY,

        /** Approximate numbers */
        'float' => SchemaInterface::TYPE_FLOAT,
        'real' => SchemaInterface::TYPE_FLOAT,
        'double' => SchemaInterface::TYPE_DOUBLE,

        /** Date and time */
        'date' => SchemaInterface::TYPE_DATE,
        'time' => SchemaInterface::TYPE_TIME,
        'smalldatetime' => SchemaInterface::TYPE_DATETIME,
        'datetime' => SchemaInterface::TYPE_DATETIME,
        'datetime2' => SchemaInterface::TYPE_DATETIME,
        'datetimeoffset' => SchemaInterface::TYPE_DATETIME,

        /** Character strings */
        'char' => SchemaInterface::TYPE_CHAR,
        'varchar' => SchemaInterface::TYPE_STRING,
        'text' => SchemaInterface::TYPE_TEXT,

        /** Unicode character strings */
        'nchar' => SchemaInterface::TYPE_CHAR,
        'nvarchar' => SchemaInterface::TYPE_STRING,
        'ntext' => SchemaInterface::TYPE_TEXT,

        /** Binary strings */
        'binary' => SchemaInterface::TYPE_BINARY,
        'varbinary' => SchemaInterface::TYPE_BINARY,
        'image' => SchemaInterface::TYPE_BINARY,

        /**
         * Other data types 'cursor' type can't be used with tables
         */
        'timestamp' => SchemaInterface::TYPE_TIMESTAMP,
        'hierarchyid' => SchemaInterface::TYPE_STRING,
        'uniqueidentifier' => SchemaInterface::TYPE_STRING,
        'sql_variant' => SchemaInterface::TYPE_STRING,
        'xml' => SchemaInterface::TYPE_STRING,
        'table' => SchemaInterface::TYPE_STRING,
    ];

    protected function getType(string $dbType, array $info = []): string
    {
        return self::TYPE_MAP[$dbType] ?? SchemaInterface::TYPE_STRING;
    }

    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        if ($type === SchemaInterface::TYPE_BINARY) {
            return (new BinaryColumnSchema($type))->load($info);
        }

        return parent::fromType($type, $info);
    }
}
