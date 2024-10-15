<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Mssql\Column\ColumnDefinitionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function preg_replace;

/**
 * Implements the MSSQL Server specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @psalm-var string[] $typeMap Mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        PseudoType::PK => 'int IDENTITY PRIMARY KEY',
        PseudoType::UPK => 'int IDENTITY PRIMARY KEY',
        PseudoType::BIGPK => 'bigint IDENTITY PRIMARY KEY',
        PseudoType::UBIGPK => 'bigint IDENTITY PRIMARY KEY',
        ColumnType::CHAR => 'nchar(1)',
        ColumnType::STRING => 'nvarchar(255)',
        ColumnType::TEXT => 'nvarchar(max)',
        ColumnType::TINYINT => 'tinyint',
        ColumnType::SMALLINT => 'smallint',
        ColumnType::INTEGER => 'int',
        ColumnType::BIGINT => 'bigint',
        ColumnType::FLOAT => 'float',
        ColumnType::DOUBLE => 'float',
        ColumnType::DECIMAL => 'decimal(18,0)',
        ColumnType::DATETIME => 'datetime',
        ColumnType::TIMESTAMP => 'datetime',
        ColumnType::TIME => 'time',
        ColumnType::DATE => 'date',
        ColumnType::BINARY => 'varbinary(max)',
        ColumnType::BOOLEAN => 'bit',
        ColumnType::MONEY => 'decimal(19,4)',
        ColumnType::UUID => 'UNIQUEIDENTIFIER',
        PseudoType::UUID_PK => 'UNIQUEIDENTIFIER PRIMARY KEY',
    ];

    public function __construct(QuoterInterface $quoter, SchemaInterface $schema)
    {
        $ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $dqlBuilder = new DQLQueryBuilder($this, $quoter);
        $columnDefinitionBuilder = new ColumnDefinitionBuilder($this);

        parent::__construct($quoter, $schema, $ddlBuilder, $dmlBuilder, $dqlBuilder, $columnDefinitionBuilder);
    }

    /** @deprecated Use {@see buildColumnDefinition()}. Will be removed in version 2.0. */
    public function getColumnType(ColumnInterface|string $type): string
    {
        /** @psalm-suppress DeprecatedMethod */
        $columnType = parent::getColumnType($type);

        /** remove unsupported keywords*/
        $columnType = preg_replace("/\s*comment '.*'/i", '', $columnType);
        return preg_replace('/ first$/i', '', $columnType);
    }
}
