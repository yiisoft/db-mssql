<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use function array_map;
use function count;
use function explode;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function strcasecmp;
use function stripos;
use Throwable;
use function version_compare;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ConstraintFinderInterface;
use Yiisoft\Db\Constraint\ConstraintFinderTrait;

use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\Schema as AbstractSchema;
use Yiisoft\Db\View\ViewFinderTrait;

/**
 * Schema is the class for retrieving metadata from MS SQL Server databases (version 2008 and above).
 */
final class Schema extends AbstractSchema implements ConstraintFinderInterface
{
    use ConstraintFinderTrait;
    use ViewFinderTrait;

    /**
     * @var string|null the default schema used for the current session.
     */
    protected ?string $defaultSchema = 'dbo';

    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     */
    private array $typeMap = [
        /** exact numbers */
        'bigint' => self::TYPE_BIGINT,
        'numeric' => self::TYPE_DECIMAL,
        'bit' => self::TYPE_SMALLINT,
        'smallint' => self::TYPE_SMALLINT,
        'decimal' => self::TYPE_DECIMAL,
        'smallmoney' => self::TYPE_MONEY,
        'int' => self::TYPE_INTEGER,
        'tinyint' => self::TYPE_TINYINT,
        'money' => self::TYPE_MONEY,

        /** approximate numbers */
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,

        /** date and time */
        'date' => self::TYPE_DATE,
        'datetimeoffset' => self::TYPE_DATETIME,
        'datetime2' => self::TYPE_DATETIME,
        'smalldatetime' => self::TYPE_DATETIME,
        'datetime' => self::TYPE_DATETIME,
        'time' => self::TYPE_TIME,

        /** character strings */
        'char' => self::TYPE_CHAR,
        'varchar' => self::TYPE_STRING,
        'text' => self::TYPE_TEXT,

        /** unicode character strings */
        'nchar' => self::TYPE_CHAR,
        'nvarchar' => self::TYPE_STRING,
        'ntext' => self::TYPE_TEXT,

        /** binary strings */
        'binary' => self::TYPE_BINARY,
        'varbinary' => self::TYPE_BINARY,
        'image' => self::TYPE_BINARY,

        /**
         * other data types 'cursor' type cannot be used with tables
         */
        'timestamp' => self::TYPE_TIMESTAMP,
        'hierarchyid' => self::TYPE_STRING,
        'uniqueidentifier' => self::TYPE_STRING,
        'sql_variant' => self::TYPE_STRING,
        'xml' => self::TYPE_STRING,
        'table' => self::TYPE_STRING,
    ];

    /** @var array|string */
    protected $tableQuoteCharacter = ['[', ']'];

    /** @var array|string */
    protected $columnQuoteCharacter = ['[', ']'];

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name the table name.
     *
     * @return TableSchema resolved table, schema, etc. names.
     */
    protected function resolveTableName(string $name): TableSchema
    {
        $resolvedName = new TableSchema();

        $parts = $this->getTableNameParts($name);

        $partCount = count($parts);

        if ($partCount === 4) {
            /** server name, catalog name, schema name and table name passed */
            $resolvedName->catalogName($parts[1]);
            $resolvedName->schemaName($parts[2]);
            $resolvedName->name($parts[3]);
            $resolvedName->fullName(
                $resolvedName->getCatalogName() . '.' . $resolvedName->getSchemaName() . '.' . $resolvedName->getName()
            );
        } elseif ($partCount === 3) {
            /** catalog name, schema name and table name passed */
            $resolvedName->catalogName($parts[0]);
            $resolvedName->schemaName($parts[1]);
            $resolvedName->name($parts[2]);
            $resolvedName->fullName(
                $resolvedName->getCatalogName() . '.' . $resolvedName->getSchemaName() . '.' . $resolvedName->getName()
            );
        } elseif ($partCount === 2) {
            /** only schema name and table name passed */
            $resolvedName->schemaName($parts[0]);
            $resolvedName->name($parts[1]);
            $resolvedName->fullName(
                ($resolvedName->getSchemaName() !== $this->defaultSchema
                    ? $resolvedName->getSchemaName() . '.' : '') . $resolvedName->getName()
            );
        } else {
            /** only table name passed */
            $resolvedName->schemaName($this->defaultSchema);
            $resolvedName->name($parts[0]);
            $resolvedName->fullName($resolvedName->getName());
        }

        return $resolvedName;
    }

    /**
     * Splits full table name into parts.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getTableNameParts(string $name): array
    {
        $parts = [$name];

        preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)]/', $name, $matches);

        if (isset($matches[0]) && !empty($matches[0])) {
            $parts = $matches[0];
        }

        return str_replace(['[', ']'], '', $parts);
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all schema names in the database, except system schemas.
     *
     * {@see https://docs.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-database-principals-transact-sql}
     */
    protected function findSchemaNames(): array
    {
        static $sql = <<<'SQL'
SELECT [s].[name]
FROM [sys].[schemas] AS [s]
INNER JOIN [sys].[database_principals] AS [p] ON [p].[principal_id] = [s].[principal_id]
WHERE [p].[is_fixed_role] = 0 AND [p].[sid] IS NOT NULL
ORDER BY [s].[name] ASC
SQL;

        return $this->getDb()->createCommand($sql)->queryColumn();
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all table names in the database. The names have NO schema name prefix.
     */
    protected function findTableNames(string $schema = ''): array
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<'SQL'
SELECT [t].[table_name]
FROM [INFORMATION_SCHEMA].[TABLES] AS [t]
WHERE [t].[table_schema] = :schema AND [t].[table_type] IN ('BASE TABLE', 'VIEW')
ORDER BY [t].[table_name]
SQL;

        $tables = $this->getDb()->createCommand($sql, [':schema' => $schema])->queryColumn();

        $tables = array_map(static function ($item) {
            return '[' . $item . ']';
        }, $tables);

        return $tables;
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return TableSchema|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    protected function loadTableSchema(string $name): ?TableSchema
    {
        $table = new TableSchema();

        $this->resolveTableNames($table, $name);
        $this->findPrimaryKeys($table);

        if ($this->findColumns($table)) {
            $this->findForeignKeys($table);

            return $table;
        }

        return null;
    }

    /**
     * Loads a primary key for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return Constraint|null primary key for the given table, `null` if the table has no primary key.
     */
    protected function loadTablePrimaryKey(string $tableName): ?Constraint
    {
        return $this->loadTableConstraints($tableName, 'primaryKey');
    }

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return ForeignKeyConstraint[] foreign keys for the given table.
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        return $this->loadTableConstraints($tableName, 'foreignKeys');
    }

    /**
     * Loads all indexes for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array indexes for the given table.
     */
    protected function loadTableIndexes(string $tableName): array
    {
        static $sql = <<<'SQL'
SELECT
    [i].[name] AS [name],
    [iccol].[name] AS [column_name],
    [i].[is_unique] AS [index_is_unique],
    [i].[is_primary_key] AS [index_is_primary]
FROM [sys].[indexes] AS [i]
INNER JOIN [sys].[index_columns] AS [ic]
    ON [ic].[object_id] = [i].[object_id] AND [ic].[index_id] = [i].[index_id]
INNER JOIN [sys].[columns] AS [iccol]
    ON [iccol].[object_id] = [ic].[object_id] AND [iccol].[column_id] = [ic].[column_id]
WHERE [i].[object_id] = OBJECT_ID(:fullName)
ORDER BY [ic].[key_ordinal] ASC
SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $indexes = $this->getDb()->createCommand($sql, [':fullName' => $resolvedName->getFullName()])->queryAll();
        $indexes = $this->normalizePdoRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');

        $result = [];

        foreach ($indexes as $name => $index) {
            $result[] = (new IndexConstraint())
                ->primary((bool) $index[0]['index_is_primary'])
                ->unique((bool) $index[0]['index_is_unique'])
                ->columnNames(ArrayHelper::getColumn($index, 'column_name'))
                ->name($name);
        }

        return $result;
    }

    /**
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return Constraint[] unique constraints for the given table.
     */
    protected function loadTableUniques(string $tableName): array
    {
        return $this->loadTableConstraints($tableName, 'uniques');
    }

    /**
     * Loads all check constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return CheckConstraint[] check constraints for the given table.
     */
    protected function loadTableChecks(string $tableName): array
    {
        return $this->loadTableConstraints($tableName, 'checks');
    }

    /**
     * Loads all default value constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return DefaultValueConstraint[] default value constraints for the given table.
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        return $this->loadTableConstraints($tableName, 'defaults');
    }

    /**
     * Creates a new savepoint.
     *
     * @param string $name the savepoint name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function createSavepoint(string $name): void
    {
        $this->getDb()->createCommand("SAVE TRANSACTION $name")->execute();
    }

    /**
     * Releases an existing savepoint.
     *
     * @param string $name the savepoint name.
     */
    public function releaseSavepoint(string $name): void
    {
        /** does nothing as MSSQL does not support this */
    }

    /**
     * Rolls back to a previously created savepoint.
     *
     * @param string $name the savepoint name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function rollBackSavepoint(string $name): void
    {
        $this->getDb()->createCommand("ROLLBACK TRANSACTION $name")->execute();
    }

    /**
     * Creates a column schema for the database.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema.
     *
     * @return ColumnSchema column schema instance.
     */
    protected function createColumnSchema(): ColumnSchema
    {
        return new ColumnSchema();
    }

    /**
     * Creates a query builder for the MSSQL database.
     *
     * @return QueryBuilder query builder interface.
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->getDb());
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param TableSchema $table the table metadata object.
     * @param string $name the table name
     */
    protected function resolveTableNames(TableSchema $table, string $name): void
    {
        $parts = $this->getTableNameParts($name);

        $partCount = count($parts);

        if ($partCount === 4) {
            /** server name, catalog name, schema name and table name passed */
            $table->catalogName($parts[1]);
            $table->schemaName($parts[2]);
            $table->name($parts[3]);
            $table->fullName($table->getCatalogName() . '.' . $table->getSchemaName() . '.' . $table->getName());
        } elseif ($partCount === 3) {
            /** catalog name, schema name and table name passed */
            $table->catalogName($parts[0]);
            $table->schemaName($parts[1]);
            $table->name($parts[2]);
            $table->fullName($table->getCatalogName() . '.' . $table->getSchemaName() . '.' . $table->getName());
        } elseif ($partCount === 2) {
            /** only schema name and table name passed */
            $table->schemaName($parts[0]);
            $table->name($parts[1]);
            $table->fullName(
                $table->getSchemaName() !== $this->defaultSchema
                ? $table->getSchemaName() . '.' . $table->getName() : $table->getName()
            );
        } else {
            /** only table name passed */
            $table->schemaName($this->defaultSchema);
            $table->name($parts[0]);
            $table->fullName($table->getName());
        }
    }

    /**
     * Loads the column information into a {@see ColumnSchema} object.
     *
     * @param array $info column information.
     *
     * @return ColumnSchema the column schema object.
     */
    protected function loadColumnSchema(array $info): ColumnSchema
    {
        $column = $this->createColumnSchema();

        $column->name($info['column_name']);
        $column->allowNull($info['is_nullable'] === 'YES');
        $column->dbType($info['data_type']);
        $column->enumValues([]); // mssql has only vague equivalents to enum
        $column->primaryKey(false); // primary key will be determined in findColumns() method
        $column->autoIncrement($info['is_identity'] === 1);
        $column->unsigned(stripos($column->getDbType(), 'unsigned') !== false);
        $column->comment($info['comment'] ?? '');
        $column->type(self::TYPE_STRING);

        if (preg_match('/^(\w+)(?:\(([^)]+)\))?/', $column->getDbType(), $matches)) {
            $type = $matches[1];

            if (isset($this->typeMap[$type])) {
                $column->type($this->typeMap[$type]);
            }

            if (!empty($matches[2])) {
                $values = explode(',', $matches[2]);
                $column->precision((int) $values[0]);
                $column->size((int) $values[0]);

                if (isset($values[1])) {
                    $column->scale((int) $values[1]);
                }

                if ($column->getSize() === 1 && ($type === 'tinyint' || $type === 'bit')) {
                    $column->type('boolean');
                } elseif ($type === 'bit') {
                    if ($column->getSize() > 32) {
                        $column->type('bigint');
                    } elseif ($column->getSize() === 32) {
                        $column->type('integer');
                    }
                }
            }
        }

        $column->phpType($this->getColumnPhpType($column));

        if ($info['column_default'] === '(NULL)') {
            $info['column_default'] = null;
        }

        if (!$column->isPrimaryKey() && ($column->getType() !== 'timestamp' || $info['column_default'] !== 'CURRENT_TIMESTAMP')) {
            $column->defaultValue($column->defaultPhpTypecast($info['column_default']));
        }

        return $column;
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchema $table the table metadata.
     *
     * @throws Throwable
     *
     * @return bool whether the table exists in the database.
     */
    protected function findColumns(TableSchema $table): bool
    {
        $columnsTableName = 'INFORMATION_SCHEMA.COLUMNS';
        $whereSql = '[t1].[table_name] = ' . $this->getDb()->quoteValue($table->getName());

        if ($table->getCatalogName() !== null) {
            $columnsTableName = "{$table->getCatalogName()}.{$columnsTableName}";
            $whereSql .= " AND [t1].[table_catalog] = '{$table->getCatalogName()}'";
        }

        if ($table->getSchemaName() !== null) {
            $whereSql .= " AND [t1].[table_schema] = '{$table->getSchemaName()}'";
        }

        $columnsTableName = $this->quoteTableName($columnsTableName);

        $sql = <<<SQL
SELECT
 [t1].[column_name],
 [t1].[is_nullable],
 CASE WHEN [t1].[data_type] IN ('char','varchar','nchar','nvarchar','binary','varbinary') THEN
    CASE WHEN [t1].[character_maximum_length] = NULL OR [t1].[character_maximum_length] = -1 THEN
        [t1].[data_type]
    ELSE
        [t1].[data_type] + '(' + LTRIM(RTRIM(CONVERT(CHAR,[t1].[character_maximum_length]))) + ')'
    END
 ELSE
    [t1].[data_type]
 END AS 'data_type',
 [t1].[column_default],
 COLUMNPROPERTY(OBJECT_ID([t1].[table_schema] + '.' + [t1].[table_name]), [t1].[column_name], 'IsIdentity') AS is_identity,
 (
    SELECT CONVERT(VARCHAR, [t2].[value])
		FROM [sys].[extended_properties] AS [t2]
		WHERE
			[t2].[class] = 1 AND
			[t2].[class_desc] = 'OBJECT_OR_COLUMN' AND
			[t2].[name] = 'MS_Description' AND
			[t2].[major_id] = OBJECT_ID([t1].[TABLE_SCHEMA] + '.' + [t1].[table_name]) AND
			[t2].[minor_id] = COLUMNPROPERTY(OBJECT_ID([t1].[TABLE_SCHEMA] + '.' + [t1].[TABLE_NAME]), [t1].[COLUMN_NAME], 'ColumnID')
 ) as comment
FROM {$columnsTableName} AS [t1]
WHERE {$whereSql}
SQL;

        try {
            $columns = $this->getDb()->createCommand($sql)->queryAll();

            if (empty($columns)) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        foreach ($columns as $column) {
            $column = $this->loadColumnSchema($column);
            foreach ($table->getPrimaryKey() as $primaryKey) {
                if (strcasecmp($column->getName(), $primaryKey) === 0) {
                    $column->primaryKey(true);
                    break;
                }
            }

            if ($column->isPrimaryKey() && $column->isAutoIncrement()) {
                $table->sequenceName('');
            }

            $table->columns($column->getName(), $column);
        }

        return true;
    }

    /**
     * Collects the constraint details for the given table and constraint type.
     *
     * @param TableSchema $table
     * @param string $type either PRIMARY KEY or UNIQUE.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array each entry contains index_name and field_name.
     */
    protected function findTableConstraints(TableSchema $table, string $type): array
    {
        $keyColumnUsageTableName = 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE';
        $tableConstraintsTableName = 'INFORMATION_SCHEMA.TABLE_CONSTRAINTS';

        if ($table->getCatalogName() !== null) {
            $keyColumnUsageTableName = $table->getCatalogName() . '.' . $keyColumnUsageTableName;
            $tableConstraintsTableName = $table->getCatalogName() . '.' . $tableConstraintsTableName;
        }

        $keyColumnUsageTableName = $this->quoteTableName($keyColumnUsageTableName);
        $tableConstraintsTableName = $this->quoteTableName($tableConstraintsTableName);

        $sql = <<<SQL
SELECT
    [kcu].[constraint_name] AS [index_name],
    [kcu].[column_name] AS [field_name]
FROM {$keyColumnUsageTableName} AS [kcu]
LEFT JOIN {$tableConstraintsTableName} AS [tc] ON
    [kcu].[table_schema] = [tc].[table_schema] AND
    [kcu].[table_name] = [tc].[table_name] AND
    [kcu].[constraint_name] = [tc].[constraint_name]
WHERE
    [tc].[constraint_type] = :type AND
    [kcu].[table_name] = :tableName AND
    [kcu].[table_schema] = :schemaName
SQL;

        return $this->getDb()->createCommand(
            $sql,
            [
                ':tableName' => $table->getName(),
                ':schemaName' => $table->getSchemaName(),
                ':type' => $type,
            ]
        )->queryAll();
    }

    /**
     * Collects the primary key column details for the given table.
     *
     * @param TableSchema $table the table metadata
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function findPrimaryKeys(TableSchema $table): void
    {
        foreach ($this->findTableConstraints($table, 'PRIMARY KEY') as $row) {
            $table->primaryKey($row['field_name']);
        }
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchema $table the table metadata
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function findForeignKeys(TableSchema $table): void
    {
        $object = $table->getName();

        if ($table->getSchemaName() !== null) {
            $object = $table->getSchemaName() . '.' . $object;
        }

        if ($table->getCatalogName() !== null) {
            $object = $table->getCatalogName() . '.' . $object;
        }

        /**
         * Please refer to the following page for more details:
         * {@see http://msdn2.microsoft.com/en-us/library/aa175805(SQL.80).aspx}
         */
        $sql = <<<'SQL'
SELECT
	[fk].[name] AS [fk_name],
	[cp].[name] AS [fk_column_name],
	OBJECT_NAME([fk].[referenced_object_id]) AS [uq_table_name],
	[cr].[name] AS [uq_column_name]
FROM
	[sys].[foreign_keys] AS [fk]
	INNER JOIN [sys].[foreign_key_columns] AS [fkc] ON
		[fk].[object_id] = [fkc].[constraint_object_id]
	INNER JOIN [sys].[columns] AS [cp] ON
		[fk].[parent_object_id] = [cp].[object_id] AND
		[fkc].[parent_column_id] = [cp].[column_id]
	INNER JOIN [sys].[columns] AS [cr] ON
		[fk].[referenced_object_id] = [cr].[object_id] AND
		[fkc].[referenced_column_id] = [cr].[column_id]
WHERE
	[fk].[parent_object_id] = OBJECT_ID(:object)
SQL;

        $rows = $this->getDb()->createCommand($sql, [':object' => $object])->queryAll();

        $table->foreignKeys([]);

        foreach ($rows as $row) {
            if (!isset($table->getForeignKeys()[$row['fk_name']])) {
                $fk[$row['fk_name']][] = $row['uq_table_name'];
                $table->foreignKeys($fk);
            }

            $fk[$row['fk_name']][$row['fk_column_name']] = $row['uq_column_name'];
            $table->foreignKeys($fk);
        }
    }

    /**
     * Returns all views names in the database.
     *
     * @param string $schema the schema of the views. Defaults to empty string, meaning the current or default schema.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all views names in the database. The names have NO schema name prefix.
     */
    protected function findViewNames(string $schema = ''): array
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<'SQL'
SELECT [t].[table_name]
FROM [INFORMATION_SCHEMA].[TABLES] AS [t]
WHERE [t].[table_schema] = :schema AND [t].[table_type] = 'VIEW'
ORDER BY [t].[table_name]
SQL;

        $views = $this->getDb()->createCommand($sql, [':schema' => $schema])->queryColumn();
        $views = array_map(static function ($item) {
            return '[' . $item . ']';
        }, $views);

        return $views;
    }

    /**
     * Returns all unique indexes for the given table.
     *
     * Each array element is of the following structure:
     *
     * ```php
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchema $table the table metadata.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchema $table): array
    {
        $result = [];

        foreach ($this->findTableConstraints($table, 'UNIQUE') as $row) {
            $result[$row['index_name']][] = $row['field_name'];
        }

        return $result;
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName table name.
     * @param string $returnType return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     * - checks
     * - defaults
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return mixed constraints.
     */
    private function loadTableConstraints(string $tableName, string $returnType)
    {
        static $sql = <<<'SQL'
SELECT
    [o].[name] AS [name],
    COALESCE([ccol].[name], [dcol].[name], [fccol].[name], [kiccol].[name]) AS [column_name],
    RTRIM([o].[type]) AS [type],
    OBJECT_SCHEMA_NAME([f].[referenced_object_id]) AS [foreign_table_schema],
    OBJECT_NAME([f].[referenced_object_id]) AS [foreign_table_name],
    [ffccol].[name] AS [foreign_column_name],
    [f].[update_referential_action_desc] AS [on_update],
    [f].[delete_referential_action_desc] AS [on_delete],
    [c].[definition] AS [check_expr],
    [d].[definition] AS [default_expr]
FROM (SELECT OBJECT_ID(:fullName) AS [object_id]) AS [t]
INNER JOIN [sys].[objects] AS [o]
    ON [o].[parent_object_id] = [t].[object_id] AND [o].[type] IN ('PK', 'UQ', 'C', 'D', 'F')
LEFT JOIN [sys].[check_constraints] AS [c]
    ON [c].[object_id] = [o].[object_id]
LEFT JOIN [sys].[columns] AS [ccol]
    ON [ccol].[object_id] = [c].[parent_object_id] AND [ccol].[column_id] = [c].[parent_column_id]
LEFT JOIN [sys].[default_constraints] AS [d]
    ON [d].[object_id] = [o].[object_id]
LEFT JOIN [sys].[columns] AS [dcol]
    ON [dcol].[object_id] = [d].[parent_object_id] AND [dcol].[column_id] = [d].[parent_column_id]
LEFT JOIN [sys].[key_constraints] AS [k]
    ON [k].[object_id] = [o].[object_id]
LEFT JOIN [sys].[index_columns] AS [kic]
    ON [kic].[object_id] = [k].[parent_object_id] AND [kic].[index_id] = [k].[unique_index_id]
LEFT JOIN [sys].[columns] AS [kiccol]
    ON [kiccol].[object_id] = [kic].[object_id] AND [kiccol].[column_id] = [kic].[column_id]
LEFT JOIN [sys].[foreign_keys] AS [f]
    ON [f].[object_id] = [o].[object_id]
LEFT JOIN [sys].[foreign_key_columns] AS [fc]
    ON [fc].[constraint_object_id] = [o].[object_id]
LEFT JOIN [sys].[columns] AS [fccol]
    ON [fccol].[object_id] = [fc].[parent_object_id] AND [fccol].[column_id] = [fc].[parent_column_id]
LEFT JOIN [sys].[columns] AS [ffccol]
    ON [ffccol].[object_id] = [fc].[referenced_object_id] AND [ffccol].[column_id] = [fc].[referenced_column_id]
ORDER BY [kic].[key_ordinal] ASC, [fc].[constraint_column_id] ASC
SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $constraints = $this->getDb()->createCommand($sql, [':fullName' => $resolvedName->getFullName()])->queryAll();
        $constraints = $this->normalizePdoRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);
        $result = [
            'primaryKey' => null,
            'foreignKeys' => [],
            'uniques' => [],
            'checks' => [],
            'defaults' => [],
        ];

        foreach ($constraints as $type => $names) {
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'PK':
                        /** @var Constraint */
                        $result['primaryKey'] = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'F':
                        $result['foreignKeys'][] = (new ForeignKeyConstraint())
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(ArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ->onDelete(str_replace('_', '', $constraint[0]['on_delete']))
                            ->onUpdate(str_replace('_', '', $constraint[0]['on_update']))
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'UQ':
                        $result['uniques'][] = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'C':
                        $result['checks'][] = (new CheckConstraint())
                            ->expression($constraint[0]['check_expr'])
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'D':
                        $result['defaults'][] = (new DefaultValueConstraint())
                            ->value($constraint[0]['default_expr'])
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                }
            }
        }
        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted. If the column name is already quoted
     * or contains '(', '[[' or '{{', then this method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string the properly quoted column name.
     *
     * {@see quoteSimpleColumnName()}
     */
    public function quoteColumnName(string $name): string
    {
        if (preg_match('/^\[.*]$/', $name)) {
            return $name;
        }

        return parent::quoteColumnName($name);
    }

    /**
     * Executes the INSERT command, returning primary key values.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name => value) to be inserted into the table.
     *
     * @throws Exception|InvalidCallException|InvalidConfigException|Throwable
     *
     * @return array|false primary key values or false if the command fails.
     */
    public function insert(string $table, array $columns)
    {
        $command = $this->getDb()->createCommand()->insert($table, $columns);
        if (!$command->execute()) {
            return false;
        }

        $isVersion2005orLater = version_compare($this->getDb()->getSchema()->getServerVersion(), '9', '>=');
        $inserted = $isVersion2005orLater ? $command->getPdoStatement()->fetch() : [];

        $tableSchema = $this->getTableSchema($table);

        $result = [];
        foreach ($tableSchema->getPrimaryKey() as $name) {
            /**
             * {@see https://github.com/yiisoft/yii2/issues/13828 & https://github.com/yiisoft/yii2/issues/17474
             */
            if (isset($inserted[$name])) {
                $result[$name] = $inserted[$name];
            } elseif ($tableSchema->getColumns()[$name]->isAutoIncrement()) {
                // for a version earlier than 2005
                $result[$name] = $this->getLastInsertID($tableSchema->getSequenceName());
            } elseif (isset($columns[$name])) {
                $result[$name] = $columns[$name];
            } else {
                $result[$name] = $tableSchema->getColumns()[$name]->getDefaultValue();
            }
        }

        return $result;
    }

    /**
     * Create a column schema builder instance giving the type and value precision.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema builder.
     *
     * @param string $type type of the column. See {@see ColumnSchemaBuilder::$type}.
     * @param array|int|string|null $length length or precision of the column. See {@see ColumnSchemaBuilder::$length}.
     *
     * @return ColumnSchemaBuilder column schema builder instance
     */
    public function createColumnSchemaBuilder(string $type, $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length, $this->getDb());
    }
}
