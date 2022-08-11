<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\ColumnSchemaInterface;
use Yiisoft\Db\Schema\Schema as AbstractSchema;
use Yiisoft\Db\Schema\TableNameInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
use function array_map;
use function explode;
use function is_array;
use function md5;
use function preg_match;
use function serialize;
use function str_replace;
use function strcasecmp;
use function stripos;

/**
 * Schema is the class for retrieving metadata from MS SQL Server databases (version 2008 and above).
 *
 * @psalm-type ColumnArray = array{
 *   column_name: string,
 *   is_nullable: string,
 *   data_type: string,
 *   column_default: mixed,
 *   is_identity: string,
 *   comment: null|string
 * }
 *
 * @psalm-type ConstraintArray = array<
 *   array-key,
 *   array {
 *     name: string,
 *     column_name: string,
 *     type: string,
 *     foreign_table_schema: string|null,
 *     foreign_table_name: string|null,
 *     foreign_column_name: string|null,
 *     on_update: string,
 *     on_delete: string,
 *     check_expr: string,
 *     default_expr: string
 *   }
 * >
 */
final class Schema extends AbstractSchema
{
    public const DEFAULTS = 'defaults';

    /**
     * @var string|null the default schema used for the current session.
     */
    protected ?string $defaultSchema = 'dbo';

    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     *
     * @psalm-var string[]
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

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param TableNameInterface $name the table name.
     *
     * @return TableSchemaInterface resolved table, schema, etc. names.
     *
     * @todo also see case `wrongBehaviour` in \Yiisoft\Db\TestSupport\TestCommandTrait::batchInsertSqlProviderTrait
     */
    protected function resolveTableName(TableNameInterface $name): TableSchemaInterface
    {
        $resolvedName = new TableSchema();

        $resolvedName->serverName($name->getServerName());
        $resolvedName->catalogName($name->getCatalogName());
        $resolvedName->schemaName($name->getSchemaName() ?? $this->defaultSchema);
        $resolvedName->name($name->getTableName());
        $resolvedName->fullName((string) $name);

        return $resolvedName;
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array All schema names in the database, except system schemas.
     *
     * @link https://docs.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-database-principals-transact-sql
     */
    protected function findSchemaNames(): array
    {
        $sql = <<<SQL
        SELECT [s].[name]
        FROM [sys].[schemas] AS [s]
        INNER JOIN [sys].[database_principals] AS [p] ON [p].[principal_id] = [s].[principal_id]
        WHERE [p].[is_fixed_role] = 0 AND [p].[sid] IS NOT NULL
        ORDER BY [s].[name] ASC
        SQL;

        return $this->db->createCommand($sql)->queryColumn();
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
     * @return array All table names in the database. The names have NO schema name prefix.
     */
    protected function findTableNames(string $schema = ''): array
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<SQL
        SELECT [t].[table_name]
        FROM [INFORMATION_SCHEMA].[TABLES] AS [t]
        WHERE [t].[table_schema] = :schema AND [t].[table_type] IN ('BASE TABLE', 'VIEW')
        ORDER BY [t].[table_name]
        SQL;

        $tables = $this->db->createCommand($sql, [':schema' => $schema])->queryColumn();

        return array_map(static fn (string $item): string => '[' . $item . ']', $tables);
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param TableNameInterface $name table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return TableSchemaInterface|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    protected function loadTableSchema(TableNameInterface $name): ?TableSchemaInterface
    {
        $table = $this->resolveTableName($name);
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
     * @param TableNameInterface $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return Constraint|null The primary key for the given table, `null` if the table has no primary key.
     */
    protected function loadTablePrimaryKey(TableNameInterface $tableName): ?Constraint
    {
        /** @var mixed */
        $tablePrimaryKey = $this->loadTableConstraints($tableName, self::PRIMARY_KEY);
        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * Loads all foreign keys for the given table.
     *
     * @param TableNameInterface $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array The foreign keys for the given table.
     */
    protected function loadTableForeignKeys(TableNameInterface $tableName): array
    {
        /** @var mixed */
        $tableForeingKeys = $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);
        return is_array($tableForeingKeys) ? $tableForeingKeys : [];
    }

    /**
     * Loads all indexes for the given table.
     *
     * @param TableNameInterface $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array indexes for the given table.
     */
    protected function loadTableIndexes(TableNameInterface $tableName): array
    {
        $sql = <<<SQL
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
        $indexes = $this->db->createCommand($sql, [':fullName' => $resolvedName->getFullName()])->queryAll();

        /** @psalm-var array[] $indexes */
        $indexes = $this->normalizeRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');

        $result = [];

        /**
         * @psalm-var array<
         *   string,
         *   array<
         *     array-key,
         *     array{array-key, name: string, column_name: string, index_is_unique: string, index_is_primary: string}
         *   >
         * > $indexes
         */
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
     * @param TableNameInterface $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array The unique constraints for the given table.
     */
    protected function loadTableUniques(TableNameInterface $tableName): array
    {
        /** @var mixed */
        $tableUniques = $this->loadTableConstraints($tableName, self::UNIQUES);
        return is_array($tableUniques) ? $tableUniques : [];
    }

    /**
     * Loads all check constraints for the given table.
     *
     * @param TableNameInterface $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array The check constraints for the given table.
     */
    protected function loadTableChecks(TableNameInterface $tableName): array
    {
        /** @var mixed */
        $tableCheck = $this->loadTableConstraints($tableName, self::CHECKS);
        return is_array($tableCheck) ? $tableCheck : [];
    }

    /**
     * Loads all default value constraints for the given table.
     *
     * @param TableNameInterface $tableName table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array The default value constraints for the given table.
     */
    protected function loadTableDefaultValues(TableNameInterface $tableName): array
    {
        /** @var mixed */
        $tableDefault = $this->loadTableConstraints($tableName, self::DEFAULTS);
        return is_array($tableDefault) ? $tableDefault : [];
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
     * Loads the column information into a {@see ColumnSchemaInterface} object.
     *
     * @psalm-param ColumnArray $info The column information.
     *
     * @return ColumnSchemaInterface the column schema object.
     */
    protected function loadColumnSchema(array $info): ColumnSchemaInterface
    {
        $column = $this->createColumnSchema();

        $column->name($info['column_name']);
        $column->allowNull($info['is_nullable'] === 'YES');
        $column->dbType($info['data_type']);
        $column->enumValues([]); // mssql has only vague equivalents to enum
        $column->primaryKey(false); // primary key will be determined in findColumns() method
        $column->autoIncrement($info['is_identity'] === '1');
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
                    $column->type(self::TYPE_BOOLEAN);
                } elseif ($type === 'bit') {
                    if ($column->getSize() > 32) {
                        $column->type(self::TYPE_BIGINT);
                    } elseif ($column->getSize() === 32) {
                        $column->type(self::TYPE_INTEGER);
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
     * @param TableSchemaInterface $table the table metadata.
     *
     * @throws Throwable
     *
     * @return bool whether the table exists in the database.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $columnsTableName = 'INFORMATION_SCHEMA.COLUMNS';
        $whereSql = '[t1].[table_name] = ' . (string) $this->db->getQuoter()->quoteValue($table->getName());

        if ($table->getCatalogName() !== null) {
            $columnsTableName = "{$table->getCatalogName()}.$columnsTableName";
            $whereSql .= " AND [t1].[table_catalog] = '{$table->getCatalogName()}'";
        }

        if ($table->getSchemaName() !== null) {
            $whereSql .= " AND [t1].[table_schema] = '{$table->getSchemaName()}'";
        }

        $columnsTableName = $this->db->getQuoter()->quoteTableName($columnsTableName);

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
        FROM $columnsTableName AS [t1]
        WHERE $whereSql
        SQL;

        try {
            /** @psalm-var ColumnArray[] */
            $columns = $this->db->createCommand($sql)->queryAll();

            if (empty($columns)) {
                return false;
            }
        } catch (Exception) {
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
     * @param TableSchemaInterface $table
     * @param string $type either PRIMARY KEY or UNIQUE.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array each entry contains index_name and field_name.
     */
    protected function findTableConstraints(TableSchemaInterface $table, string $type): array
    {
        $keyColumnUsageTableName = 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE';
        $tableConstraintsTableName = 'INFORMATION_SCHEMA.TABLE_CONSTRAINTS';

        $catalogName = $table->getCatalogName();
        if ($catalogName !== null) {
            $keyColumnUsageTableName = $catalogName . '.' . $keyColumnUsageTableName;
            $tableConstraintsTableName = $catalogName . '.' . $tableConstraintsTableName;
        }

        $keyColumnUsageTableName = $this->db->getQuoter()->quoteTableName($keyColumnUsageTableName);
        $tableConstraintsTableName = $this->db->getQuoter()->quoteTableName($tableConstraintsTableName);

        $sql = <<<SQL
        SELECT
            [kcu].[constraint_name] AS [index_name],
            [kcu].[column_name] AS [field_name]
        FROM $keyColumnUsageTableName AS [kcu]
        LEFT JOIN $tableConstraintsTableName AS [tc] ON
            [kcu].[table_schema] = [tc].[table_schema] AND
            [kcu].[table_name] = [tc].[table_name] AND
            [kcu].[constraint_name] = [tc].[constraint_name]
        WHERE
            [tc].[constraint_type] = :type AND
            [kcu].[table_name] = :tableName AND
            [kcu].[table_schema] = :schemaName
        SQL;

        return $this->db->createCommand(
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
     * @param TableSchemaInterface $table the table metadata
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function findPrimaryKeys(TableSchemaInterface $table): void
    {
        /** @psalm-var array<array-key, array{index_name: string, field_name: string}> $primaryKeys */
        $primaryKeys = $this->findTableConstraints($table, 'PRIMARY KEY');

        foreach ($primaryKeys as $row) {
            $table->primaryKey($row['field_name']);
        }
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchemaInterface $table the table metadata
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function findForeignKeys(TableSchemaInterface $table): void
    {
        $catalogName = $table->getCatalogName();
        $fk = [];
        $object = $table->getName();
        $schemaName = $table->getSchemaName();

        if ($schemaName !== null) {
            $object = $schemaName . '.' . $object;
        }

        if ($catalogName !== null) {
            $object = $catalogName . '.' . $object;
        }

        /**
         * Please refer to the following page for more details:
         * {@see http://msdn2.microsoft.com/en-us/library/aa175805(SQL.80).aspx}
         */
        $sql = <<<SQL
        SELECT
        [fk].[name] AS [fk_name],
        [cp].[name] AS [fk_column_name],
        OBJECT_NAME([fk].[referenced_object_id]) AS [uq_table_name],
        [cr].[name] AS [uq_column_name]
        FROM [sys].[foreign_keys] AS [fk]
        INNER JOIN [sys].[foreign_key_columns] AS [fkc]
            ON [fk].[object_id] = [fkc].[constraint_object_id]
        INNER JOIN [sys].[columns] AS [cp]
            ON [fk].[parent_object_id] = [cp].[object_id] AND [fkc].[parent_column_id] = [cp].[column_id]
        INNER JOIN [sys].[columns] AS [cr]
            ON [fk].[referenced_object_id] = [cr].[object_id] AND [fkc].[referenced_column_id] = [cr].[column_id]
        WHERE [fk].[parent_object_id] = OBJECT_ID(:object)
        SQL;

        /**
         * @psalm-var array<
         *   array-key,
         *   array{fk_name: string, fk_column_name: string, uq_table_name: string, uq_column_name: string}
         * > $rows
         */
        $rows = $this->db->createCommand($sql, [':object' => $object])->queryAll();
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
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function findViewNames(string $schema = ''): array
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<SQL
        SELECT [t].[table_name]
        FROM [INFORMATION_SCHEMA].[TABLES] AS [t]
        WHERE [t].[table_schema] = :schema AND [t].[table_type] = 'VIEW'
        ORDER BY [t].[table_name]
        SQL;

        $views = $this->db->createCommand($sql, [':schema' => $schema])->queryColumn();

        return array_map(static fn (string $item): string => '[' . $item . ']', $views);
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
     * @param TableSchemaInterface $table the table metadata.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        $result = [];

        /** @psalm-var array<array-key, array{index_name: string, field_name: string}> $tableUniqueConstraints */
        $tableUniqueConstraints = $this->findTableConstraints($table, 'UNIQUE');

        foreach ($tableUniqueConstraints as $row) {
            $result[$row['index_name']][] = $row['field_name'];
        }

        return $result;
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param TableNameInterface $tableName table name.
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
    private function loadTableConstraints(TableNameInterface $tableName, string $returnType): mixed
    {
        $sql = <<<SQL
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
        $constraints = $this->db->createCommand($sql, [':fullName' => $resolvedName->getFullName()])->queryAll();

        /** @psalm-var array[] $constraints */
        $constraints = $this->normalizeRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
            self::CHECKS => [],
            self::DEFAULTS => [],
        ];

        /** @psalm-var array<array-key, array> $constraints */
        foreach ($constraints as $type => $names) {
            /**
             * @psalm-var object|string|null $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'PK':
                        /** @var Constraint */
                        $result[self::PRIMARY_KEY] = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'F':
                        $result[self::FOREIGN_KEYS][] = (new ForeignKeyConstraint())
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(ArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ->onDelete(str_replace('_', '', $constraint[0]['on_delete']))
                            ->onUpdate(str_replace('_', '', $constraint[0]['on_update']))
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'UQ':
                        $result[self::UNIQUES][] = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'C':
                        $result[self::CHECKS][] = (new CheckConstraint())
                            ->expression($constraint[0]['check_expr'])
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'D':
                        $result[self::DEFAULTS][] = (new DefaultValueConstraint())
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
     * Create a column schema builder instance giving the type and value precision.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema builder.
     *
     * @param string $type type of the column. See {@see ColumnSchemaBuilder::$type}.
     * @param array|int|string|null $length length or precision of the column. See {@see ColumnSchemaBuilder::$length}.
     *
     * @return ColumnSchemaBuilder column schema builder instance
     *
     * @psalm-param array<array-key, string>|int|null|string $length
     */
    public function createColumnSchemaBuilder(string $type, array|int|string $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length);
    }

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * {@see ConnectionInterface::tablePrefix}.
     *
     * @param string|TableNameInterface $name the table name to be converted.
     *
     * @return string the real name of the given table name.
     */
    public function getRawTableName(string|TableNameInterface $name): string
    {
        return (string) $name;
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name the table name.
     *
     * @return array the cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return array_merge([__CLASS__], $this->db->getCacheKey(), [$name]);
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string the cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize(array_merge([__CLASS__], $this->db->getCacheKey())));
    }

    /**
     * @return bool whether this DBMS supports [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     */
    public function supportsSavepoint(): bool
    {
        return $this->db->isSavepointEnabled();
    }

    /**
     * Changes row's array key case to lower.
     *
     * @param array $row row's array or an array of row's arrays.
     * @param bool $multiple whether multiple rows or a single row passed.
     *
     * @return array normalized row or rows.
     */
    protected function normalizeRowKeyCase(array $row, bool $multiple): array
    {
        if ($multiple) {
            return array_map(static function (array $row) {
                return array_change_key_case($row, CASE_LOWER);
            }, $row);
        }

        return array_change_key_case($row, CASE_LOWER);
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertID(?string $sequenceName = null): string
    {
        return $this->db->getLastInsertID($sequenceName);
    }
}
