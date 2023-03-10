<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function preg_replace;

final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @psalm-var string[] $typeMap Mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        SchemaInterface::TYPE_PK => 'int IDENTITY PRIMARY KEY',
        SchemaInterface::TYPE_UPK => 'int IDENTITY PRIMARY KEY',
        SchemaInterface::TYPE_BIGPK => 'bigint IDENTITY PRIMARY KEY',
        SchemaInterface::TYPE_UBIGPK => 'bigint IDENTITY PRIMARY KEY',
        SchemaInterface::TYPE_CHAR => 'nchar(1)',
        SchemaInterface::TYPE_STRING => 'nvarchar(255)',
        SchemaInterface::TYPE_TEXT => 'nvarchar(max)',
        SchemaInterface::TYPE_TINYINT => 'tinyint',
        SchemaInterface::TYPE_SMALLINT => 'smallint',
        SchemaInterface::TYPE_INTEGER => 'int',
        SchemaInterface::TYPE_BIGINT => 'bigint',
        SchemaInterface::TYPE_FLOAT => 'float',
        SchemaInterface::TYPE_DOUBLE => 'float',
        SchemaInterface::TYPE_DECIMAL => 'decimal(18,0)',
        SchemaInterface::TYPE_DATETIME => 'datetime',
        SchemaInterface::TYPE_TIMESTAMP => 'datetime',
        SchemaInterface::TYPE_TIME => 'time',
        SchemaInterface::TYPE_DATE => 'date',
        SchemaInterface::TYPE_BINARY => 'varbinary(max)',
        SchemaInterface::TYPE_BOOLEAN => 'bit',
        SchemaInterface::TYPE_MONEY => 'decimal(19,4)',
    ];
    private DDLQueryBuilder $ddlBuilder;
    private DMLQueryBuilder $dmlBuilder;
    private DQLQueryBuilder $dqlBuilder;

    public function __construct(QuoterInterface $quoter, SchemaInterface $schema)
    {
        $this->ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $this->dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $this->dqlBuilder = new DQLQueryBuilder($this, $quoter, $schema);
        parent::__construct($quoter, $schema, $this->ddlBuilder, $this->dmlBuilder, $this->dqlBuilder);
    }

    public function getColumnType(ColumnInterface|string $type): string
    {
        $columnType = parent::getColumnType($type);

        /** remove unsupported keywords*/
        $columnType = preg_replace("/\s*comment '.*'/i", '', $columnType);
        return preg_replace('/ first$/i', '', $columnType);
    }
}
