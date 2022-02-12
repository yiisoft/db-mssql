<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\PDO;

use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Condition\InConditionBuilder;
use Yiisoft\Db\Mssql\Condition\LikeConditionBuilder;
use Yiisoft\Db\Mssql\DDLQueryBuilder;
use Yiisoft\Db\Mssql\DMLQueryBuilder;
use Yiisoft\Db\Query\Conditions\InCondition;
use Yiisoft\Db\Query\Conditions\LikeCondition;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_keys;
use function preg_match;
use function preg_replace;

/**
 * QueryBuilder is the query builder for MS SQL Server databases (version 2008 and above).
 */
final class QueryBuilderPDOMssql extends QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        Schema::TYPE_PK => 'int IDENTITY PRIMARY KEY',
        Schema::TYPE_UPK => 'int IDENTITY PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigint IDENTITY PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigint IDENTITY PRIMARY KEY',
        Schema::TYPE_CHAR => 'nchar(1)',
        Schema::TYPE_STRING => 'nvarchar(255)',
        Schema::TYPE_TEXT => 'nvarchar(max)',
        Schema::TYPE_TINYINT => 'tinyint',
        Schema::TYPE_SMALLINT => 'smallint',
        Schema::TYPE_INTEGER => 'int',
        Schema::TYPE_BIGINT => 'bigint',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DOUBLE => 'float',
        Schema::TYPE_DECIMAL => 'decimal(18,0)',
        Schema::TYPE_DATETIME => 'datetime',
        Schema::TYPE_TIMESTAMP => 'datetime',
        Schema::TYPE_TIME => 'time',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'varbinary(max)',
        Schema::TYPE_BOOLEAN => 'bit',
        Schema::TYPE_MONEY => 'decimal(19,4)',
    ];

    public function __construct(
        private CommandInterface $command,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        $this->ddlBuilder = new DDLQueryBuilder($this);
        $this->dmlBuilder = new DMLQueryBuilder($this);
        parent::__construct($quoter, $schema);
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return $this->ddlBuilder->addCommentOnColumn($table, $column, $comment);
    }

    public function addCommentOnTable(string $table, string $comment): string
    {
        return $this->ddlBuilder->addCommentOnTable($table, $comment);
    }

    public function alterColumn(string $table, string $column, string $type): string
    {
        $type = $this->getColumnType($type);
        return $this->ddlBuilder->alterColumn($table, $column, $type);
    }

    public function buildOrderByAndLimit(string $sql, array $orderBy, $limit, $offset, array &$params = []): string
    {
        if (!$this->hasOffset($offset) && !$this->hasLimit($limit)) {
            $orderBy = $this->buildOrderBy($orderBy, $params);

            return $orderBy === '' ? $sql : $sql . $this->separator . $orderBy;
        }

        return $this->newBuildOrderByAndLimit($sql, $orderBy, $limit, $offset, $params);
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        return $this->ddlBuilder->checkIntegrity($schema, $table, $check);
    }

    public function command(): CommandInterface
    {
        return $this->command;
    }

    public function dropCommentFromColumn(string $table, string $column): string
    {
        return $this->ddlBuilder->dropCommentFromColumn($table, $column);
    }

    public function dropCommentFromTable(string $table): string
    {
        return $this->ddlBuilder->dropCommentFromTable($table);
    }

    public function getColumnType(ColumnSchemaBuilder|string $type): string
    {
        $columnType = parent::getColumnType($type);

        /** remove unsupported keywords*/
        $columnType = preg_replace("/\s*comment '.*'/i", '', $columnType);
        return preg_replace('/ first$/i', '', $columnType);
    }

    public function quoter(): QuoterInterface
    {
        return $this->quoter;
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return 'sp_rename ' .
            $this->quoter->quoteTableName($oldName) . ', ' . $this->quoter->quoteTableName($newName);
    }

    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        $table = $this->quoter->quoteTableName($table);
        $oldName = $this->quoter->quoteColumnName($oldName);
        $newName = $this->quoter->quoteColumnName($newName);

        return "sp_rename '$table.$oldName', $newName, 'COLUMN'";
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END';
    }

    public function schema(): SchemaInterface
    {
        return $this->schema;
    }

    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            InCondition::class => InConditionBuilder::class,
            LikeCondition::class => LikeConditionBuilder::class,
        ]);
    }

    /**
     * Builds the ORDER BY/LIMIT/OFFSET clauses for SQL SERVER 2012 or newer.
     *
     * @param string $sql the existing SQL (without ORDER BY/LIMIT/OFFSET).
     * @param array $orderBy the order by columns. See {@see Query::orderBy} for more details on how to specify this
     * parameter.
     * @param Expression|Query|int|null $limit the limit number. See {@see Query::limit} for more details.
     * @param Expression|Query|int|null $offset the offset number. See {@see Query::offset} for more details.
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL completed with ORDER BY/LIMIT/OFFSET (if any).
     */
    protected function newBuildOrderByAndLimit(
        string $sql,
        array $orderBy,
        Expression|Query|int|null $limit,
        Expression|Query|int|null $offset,
        array &$params = []
    ): string {
        $orderBy = $this->buildOrderBy($orderBy, $params);

        if ($orderBy === '') {
            /** ORDER BY clause is required when FETCH and OFFSET are in the SQL */
            $orderBy = 'ORDER BY (SELECT NULL)';
        }

        $sql .= $this->separator . $orderBy;

        /**
         * {@see http://technet.microsoft.com/en-us/library/gg699618.aspx}
         */
        $offset = $this->hasOffset($offset) ? $offset : '0';
        $sql .= $this->separator . "OFFSET $offset ROWS";

        if ($this->hasLimit($limit)) {
            $sql .= $this->separator . "FETCH NEXT $limit ROWS ONLY";
        }

        return $sql;
    }

    /**
     * Returns an array of column names given model name.
     *
     * @param string|null $modelClass name of the model class.
     *
     * @return array|null array of column names
     */
    protected function getAllColumnNames(string $modelClass = null): ?array
    {
        if (!$modelClass) {
            return null;
        }

        $schema = $modelClass::getTableSchema();

        return array_keys($schema->columns);
    }

    /**
     * Extracts table alias if there is one or returns false
     *
     * @param string $table
     *
     * @return array|bool
     * @psalm-return array<array-key, string>|bool
     */
    protected function extractAlias(string $table): array|bool
    {
        if (preg_match('/^\[.*]$/', $table)) {
            return false;
        }

        return parent::extractAlias($table);
    }
}
