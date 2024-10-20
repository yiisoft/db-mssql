<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

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
        'timestamp' => ColumnType::BINARY,
        'hierarchyid' => ColumnType::STRING,
        'uniqueidentifier' => ColumnType::UUID,
        'sql_variant' => ColumnType::STRING,
        'xml' => ColumnType::STRING,
        'table' => ColumnType::STRING,
    ];

    protected function getType(string $dbType, array $info = []): string
    {
        return self::TYPE_MAP[$dbType] ?? ColumnType::STRING;
    }

    public function fromPseudoType(string $pseudoType, array $info = []): ColumnSchemaInterface
    {
        if ($pseudoType === PseudoType::UUID_PK_SEQ) {
            unset($info['type']);
            $info['primaryKey'] = true;
            $info['autoIncrement'] = true;
            $info['defaultValue'] = new Expression('newsequentialid()');

            return new StringColumnSchema(ColumnType::UUID, ...$info);
        }

        return parent::fromPseudoType($pseudoType, $info);
    }

    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        if ($type === ColumnType::BINARY) {
            unset($info['type']);
            return new BinaryColumnSchema($type, ...$info);
        }

        return parent::fromType($type, $info);
    }

    protected function isDbType(string $dbType): bool
    {
        return isset(self::TYPE_MAP[$dbType]);
    }
}
