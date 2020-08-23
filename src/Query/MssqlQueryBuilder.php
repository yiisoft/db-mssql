<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Query;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\Mssql\Schema\MssqlSchema;

/**
 * MssqlQueryBuilder is the query builder for MS SQL Server databases (version 2008 and above).
 */
final class MssqlQueryBuilder extends QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        MssqlSchema::TYPE_PK => 'int IDENTITY PRIMARY KEY',
        MssqlSchema::TYPE_UPK => 'int IDENTITY PRIMARY KEY',
        MssqlSchema::TYPE_BIGPK => 'bigint IDENTITY PRIMARY KEY',
        MssqlSchema::TYPE_UBIGPK => 'bigint IDENTITY PRIMARY KEY',
        MssqlSchema::TYPE_CHAR => 'nchar(1)',
        MssqlSchema::TYPE_STRING => 'nvarchar(255)',
        MssqlSchema::TYPE_TEXT => 'nvarchar(max)',
        MssqlSchema::TYPE_TINYINT => 'tinyint',
        MssqlSchema::TYPE_SMALLINT => 'smallint',
        MssqlSchema::TYPE_INTEGER => 'int',
        MssqlSchema::TYPE_BIGINT => 'bigint',
        MssqlSchema::TYPE_FLOAT => 'float',
        MssqlSchema::TYPE_DOUBLE => 'float',
        MssqlSchema::TYPE_DECIMAL => 'decimal(18,0)',
        MssqlSchema::TYPE_DATETIME => 'datetime',
        MssqlSchema::TYPE_TIMESTAMP => 'datetime',
        MssqlSchema::TYPE_TIME => 'time',
        MssqlSchema::TYPE_DATE => 'date',
        MssqlSchema::TYPE_BINARY => 'varbinary(max)',
        MssqlSchema::TYPE_BOOLEAN => 'bit',
        MssqlSchema::TYPE_MONEY => 'decimal(19,4)',
    ];

    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            Yiisoft\Db\Query\Conditions\InCondition::class => Yiisoft\Db\Mssql\Condition\InConditionBuilder::class,
            Yiisoft\Db\Query\Conditions\LikeCondition::class => Yiisoft\Db\Mssql\Condition\LikeConditionBuilder::class,
        ]);
    }

    public function buildOrderByAndLimit(string $sql, array $orderBy, $limit, $offset, array &$params = []): string
    {
        if (!$this->hasOffset($offset) && !$this->hasLimit($limit)) {
            $orderBy = $this->buildOrderBy($orderBy, $params);

            return $orderBy === '' ? $sql : $sql . $this->separator . $orderBy;
        }

        if (version_compare($this->getDb()->getSchema()->getServerVersion(), '11', '<')) {
            return $this->oldBuildOrderByAndLimit($sql, $orderBy, $limit, $offset, $params);
        }

        return $this->newBuildOrderByAndLimit($sql, $orderBy, $limit, $offset, $params);
    }

    /**
     * Builds the ORDER BY/LIMIT/OFFSET clauses for SQL SERVER 2012 or newer.
     *
     * @param string $sql the existing SQL (without ORDER BY/LIMIT/OFFSET)
     * @param array $orderBy the order by columns. See {@see \Yiisoft\Db\Query::orderBy} for more details on how to
     * specify this parameter.
     * @param int $limit the limit number. See {@see \Yiisoft\Db\Query::limit} for more details.
     * @param int $offset the offset number. See {@see \Yiisoft\Db\Query::offset} for more details.
     * @param array $params the binding parameters to be populated.
     *
     * @return string the SQL completed with ORDER BY/LIMIT/OFFSET (if any)
     */
    protected function newBuildOrderByAndLimit($sql, $orderBy, $limit, $offset, &$params)
    {
        $orderBy = $this->buildOrderBy($orderBy, $params);
        if ($orderBy === '') {
            // ORDER BY clause is required when FETCH and OFFSET are in the SQL
            $orderBy = 'ORDER BY (SELECT NULL)';
        }

        $sql .= $this->separator . $orderBy;

        // http://technet.microsoft.com/en-us/library/gg699618.aspx
        $offset = $this->hasOffset($offset) ? $offset : '0';
        $sql .= $this->separator . "OFFSET $offset ROWS";

        if ($this->hasLimit($limit)) {
            $sql .= $this->separator . "FETCH NEXT $limit ROWS ONLY";
        }

        return $sql;
    }

    /**
     * Builds the ORDER BY/LIMIT/OFFSET clauses for SQL SERVER 2005 to 2008.
     *
     * @param string $sql the existing SQL (without ORDER BY/LIMIT/OFFSET).
     * @param array $orderBy the order by columns. See {@see \Yiisoft\Db\Query::orderBy} for more details on how to
     * specify this parameter.
     * @param int $limit the limit number. See {@see \Yiisoft\Db\Query::limit} for more details.
     * @param int $offset the offset number. See {@see \Yiisoft\Db\Query::offset} for more details.
     * @param array $params the binding parameters to be populated.
     *
     * @return string the SQL completed with ORDER BY/LIMIT/OFFSET (if any).
     */
    protected function oldBuildOrderByAndLimit($sql, $orderBy, $limit, $offset, &$params)
    {
        $orderBy = $this->buildOrderBy($orderBy, $params);
        if ($orderBy === '') {
            // ROW_NUMBER() requires an ORDER BY clause
            $orderBy = 'ORDER BY (SELECT NULL)';
        }

        $sql = preg_replace(
            '/^([\s(])*SELECT(\s+DISTINCT)?(?!\s*TOP\s*\()/i',
            "\\1SELECT\\2 rowNum = ROW_NUMBER() over ($orderBy),",
            $sql
        );

        if ($this->hasLimit($limit)) {
            $sql = "SELECT TOP $limit * FROM ($sql) sub";
        } else {
            $sql = "SELECT * FROM ($sql) sub";
        }

        if ($this->hasOffset($offset)) {
            $sql .= $this->separator . "WHERE rowNum > $offset";
        }

        return $sql;
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     *
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable(string $oldName, string $newName): string
    {
        return 'sp_rename ' .
            $this->getDb()->quoteTableName($oldName) . ', ' . $this->getDb()->quoteTableName($newName);
    }

    /**
     * Builds a SQL statement for renaming a column.
     *
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        $table = $this->getDb()->quoteTableName($table);
        $oldName = $this->getDb()->quoteColumnName($oldName);
        $newName = $this->getDb()->quoteColumnName($newName);

        return "sp_rename '{$table}.{$oldName}', {$newName}, 'COLUMN'";
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     *
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[getColumnType]] method will be invoked to convert abstract column
     * type (if any) into the physical one. Anything that is not recognized as abstract type will be kept in the
     * generated SQL.
     *
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'.
     *
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn(string $table, string $column, string $type): string
    {
        $type = $this->getColumnType($type);
        $sql = 'ALTER TABLE ' . $this->getDb()->quoteTableName($table) . ' ALTER COLUMN '
            . $this->getDb()->quoteColumnName($column) . ' '
            . $this->getColumnType($type);

        return $sql;
    }

    public function addDefaultValue(string $name, string $table, string $column, $value): string
    {
        return 'ALTER TABLE ' . $this->getDb()->quoteTableName($table) . ' ADD CONSTRAINT '
            . $this->getDb()->quoteColumnName($name) . ' DEFAULT ' . $this->getDb()->quoteValue($value) . ' FOR '
            . $this->getDb()->quoteColumnName($column);
    }

    public function dropDefaultValue(string $name, string $table): string
    {
        return 'ALTER TABLE ' .
            $this->getDb()->quoteTableName($table) . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     *
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value
     * or 1.
     *
     * @param string $tableName the name of the table whose primary key sequence will be reset.
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set, the next new
     * row's primary key will have a value 1.
     *
     * @return string the SQL statement for resetting sequence.
     *
     * @throws InvalidArgumentException if the table does not exist or there is no sequence associated with the table.
     */
    public function resetSequence(string $tableName, $value = null): string
    {
        $table = $this->getDb()->getTableSchema($tableName);

        if ($table !== null && $table->getSequenceName() !== null) {
            $tableName = $this->db->quoteTableName($tableName);

            if ($value === null) {
                $key = $this->getDb()->quoteColumnName(reset($table->getPrimaryKey()));
                $value = "(SELECT COALESCE(MAX({$key}),0) FROM {$tableName})+1";
            } else {
                $value = (int) $value;
            }

            return "DBCC CHECKIDENT ('{$tableName}', RESEED, {$value})";
        } elseif ($table === null) {
            throw new InvalidArgumentException("Table not found: $tableName");
        }

        throw new InvalidArgumentException("There is not sequence associated with table '$tableName'.");
    }

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     *
     * @param bool $check whether to turn on or off the integrity check.
     * @param string $schema the schema of the tables.
     * @param string $table the table name.
     *
     * @return string the SQL statement for checking integrity.
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        $enable = $check ? 'CHECK' : 'NOCHECK';
        $schema = $schema ?: $this->getDb()->getSchema()->defaultSchema;
        $tableNames = $this->getDb()->getTableSchema($table)
            ? [$table] : $this->getDb()->getSchema()->getTableNames($schema);
        $viewNames = $this->getDb()->getSchema()->getViewNames($schema);
        $tableNames = array_diff($tableNames, $viewNames);
        $command = '';

        foreach ($tableNames as $tableName) {
            $tableName = $this->getDb()->quoteTableName("{$schema}.{$tableName}");
            $command .= "ALTER TABLE $tableName $enable CONSTRAINT ALL; ";
        }

        return $command;
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return "sp_updateextendedproperty @name = N'MS_Description', @value = {$this->db->quoteValue($comment)}, @level1type = N'Table',  @level1name = {$this->db->quoteTableName($table)}, @level2type = N'Column', @level2name = {$this->db->quoteColumnName($column)}";
    }

    public function addCommentOnTable(string $table, string $comment): string
    {
        return "sp_updateextendedproperty @name = N'MS_Description', @value = {$this->db->quoteValue($comment)}, @level1type = N'Table',  @level1name = {$this->db->quoteTableName($table)}";
    }

    public function dropCommentFromColumn(string $table, string $column): string
    {
        return "sp_dropextendedproperty @name = N'MS_Description', @level1type = N'Table',  @level1name = {$this->db->quoteTableName($table)}, @level2type = N'Column', @level2name = {$this->db->quoteColumnName($column)}";
    }

    public function dropCommentFromTable(string $table): string
    {
        return "sp_dropextendedproperty @name = N'MS_Description', @level1type = N'Table',  @level1name = {$this->db->quoteTableName($table)}";
    }

    /**
     * Returns an array of column names given model name.
     *
     * @param string $modelClass name of the model class
     * @return array|null array of column names
     */
    protected function getAllColumnNames($modelClass = null)
    {
        if (!$modelClass) {
            return null;
        }
        /* @var $modelClass \Yiisoft\Db\ActiveRecord */
        $schema = $modelClass::getTableSchema();
        return array_keys($schema->columns);
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END';
    }

    /**
     * Normalizes data to be saved into the table, performing extra preparations and type converting, if necessary.
     *
     * @param string $table the table that data will be saved into.
     * @param array $columns the column data (name => value) to be saved into the table.
     *
     * @return array normalized columns
     */
    private function normalizeTableRowData($table, $columns, &$params)
    {
        if (($tableSchema = $this->db->getSchema()->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->columns;
            foreach ($columns as $name => $value) {
                // @see https://github.com/yiisoft/yii2/issues/12599
                if (isset($columnSchemas[$name]) && $columnSchemas[$name]->type === MssqlSchema::TYPE_BINARY && $columnSchemas[$name]->dbType === 'varbinary' && is_string($value)) {
                    $exParams = [];
                    $phName = $this->bindParam($value, $exParams);
                    $columns[$name] = new Expression("CONVERT(VARBINARY, $phName)", $exParams);
                }
            }
        }

        return $columns;
    }

    public function insert(string $table, $columns, array &$params = []): string
    {
        return parent::insert($table, $this->normalizeTableRowData($table, $columns, $params), $params);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.microsoft.com/en-us/sql/t-sql/statements/merge-transact-sql
     * @see http://weblogs.sqlteam.com/dang/archive/2009/01/31/UPSERT-Race-Condition-With-MERGE.aspx
     */
    public function upsert(string $table, $insertColumns, $updateColumns, array &$params): string
    {
        /** @var Constraint[] $constraints */
        $constraints = [];

        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        $onCondition = ['or'];
        $quotedTableName = $this->getDb()->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $constraintCondition = ['and'];

            foreach ($constraint->columnNames as $name) {
                $quotedName = $this->getDb()->quoteColumnName($name);
                $constraintCondition[] = "$quotedTableName.$quotedName=[EXCLUDED].$quotedName";
            }
            $onCondition[] = $constraintCondition;
        }

        $on = $this->buildCondition($onCondition, $params);
        [, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);
        $mergeSql = 'MERGE ' . $this->getDb()->quoteTableName($table) . ' WITH (HOLDLOCK) '
            . 'USING (' . (!empty($placeholders)
            ? 'VALUES (' . implode(', ', $placeholders) . ')'
            : ltrim($values, ' ')) . ') AS [EXCLUDED] (' . implode(', ', $insertNames) . ') ' . "ON ($on)";

        $insertValues = [];
        foreach ($insertNames as $name) {
            $quotedName = $this->db->quoteColumnName($name);

            if (strrpos($quotedName, '.') === false) {
                $quotedName = '[EXCLUDED].' . $quotedName;
            }
            $insertValues[] = $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $insertNames) . ')' . ' VALUES (' . implode(', ', $insertValues) . ')';

        if ($updateColumns === false) {
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql;";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            foreach ($updateNames as $name) {
                $quotedName = $this->getDb()->quoteColumnName($name);
                if (strrpos($quotedName, '.') === false) {
                    $quotedName = '[EXCLUDED].' . $quotedName;
                }

                $updateColumns[$name] = new Expression($quotedName);
            }
        }
        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);
        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "$mergeSql WHEN MATCHED THEN $updateSql WHEN NOT MATCHED THEN $insertSql;";
    }

    public function update(string $table, array $columns, $condition, array &$params = []): string
    {
        return parent::update($table, $this->normalizeTableRowData($table, $columns, $params), $condition, $params);
    }
}
